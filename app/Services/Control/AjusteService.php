<?php

namespace App\Services\Control;

use App\Enums\CodigoError;
use App\Enums\EstadoBulto;
use App\Enums\EstadoLegal;
use App\Enums\MotivoAjuste;
use App\Enums\TipoAjuste;
use App\Exceptions\ReglaNegocioException;
use App\Models\Ajuste;
use App\Models\Bulto;
use App\Models\BultoDetalle;
use App\Models\Movimiento;
use App\Models\User;
use App\Services\Nucleo\ConsecutivoService;
use App\Services\Nucleo\MovimientoService;
use App\Services\Nucleo\OcupacionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * CUS-019 Registrar Ajuste de Inventario (Supervisor, J1–J10, A2).
 *
 * - CUARENTENA mueve la recibida; LEGALIZADA mueve la recibida y la legalizada (J2, BultoDetalle::ajustar).
 * - Nunca deja negativos ni toca reservadas o despachadas (J3, EX-01, EX-02). Descripción obligatoria (J4, EX-03).
 * - CORRECCION_REGISTRO exige el movimiento corregido, que debe ser de la misma línea (J6, RS-57).
 * - Soporte opcional y privado; si la transacción falla se borra (J7).
 * - AJ-AAAA-00000, un movimiento AJUSTE en la bodega del pallet, ocupación recalculada y bitácora (J8).
 * - Línea EN_AFORO: solo el ajuste negativo de legalizadas que destraba el cierre (J10, A2).
 */
final class AjusteService
{
    public const CARPETA = 'ajustes';

    public function __construct(
        private readonly MovimientoService $movimientos,
        private readonly ConsecutivoService $consecutivos,
        private readonly OcupacionService $ocupacion,
    ) {}

    /** J10: en aforo solo se admite NEGATIVO en LEGALIZADA (no cambia el conteo y permite cerrar). */
    public static function permitidoEnAforo(TipoAjuste $tipo, EstadoLegal $estado): bool
    {
        return $tipo === TipoAjuste::NEGATIVO && $estado === EstadoLegal::LEGALIZADA;
    }

    public function registrar(
        BultoDetalle $linea,
        TipoAjuste $tipo,
        string $cantidad,
        EstadoLegal $estado,
        MotivoAjuste $motivo,
        string $descripcion,
        ?int $movimientoCorregidoId,
        ?UploadedFile $soporte,
        User $usuario,
    ): Ajuste {
        $descripcion = trim($descripcion);

        if ($descripcion === '') {
            throw new ReglaNegocioException(CodigoError::AJUSTE_DESCRIPCION_REQUERIDA, campo: 'descripcion');
        }
        if ($motivo->exigeMovimientoCorregido() && ! $movimientoCorregidoId) {
            throw new ReglaNegocioException(CodigoError::AJUSTE_REFERENCIA_REQUERIDA, campo: 'movimiento_corregido_id');
        }

        $ruta = $soporte ? Storage::disk('local')->putFile(self::CARPETA.'/'.now()->format('Y'), $soporte) : null;

        if ($soporte && ! $ruta) {
            throw new ReglaNegocioException(CodigoError::SOPORTE_REQUERIDO, 'No se pudo guardar el soporte. Intente de nuevo.', campo: 'soporte');
        }

        try {
            return DB::transaction(fn () => $this->registrarEnTransaccion(
                $linea, $tipo, $cantidad, $estado, $motivo, $descripcion, $movimientoCorregidoId, $ruta, $usuario,
            ));
        } catch (Throwable $e) {
            if ($ruta) {
                Storage::disk('local')->delete($ruta);
            }
            throw $e;
        }
    }

    private function registrarEnTransaccion(
        BultoDetalle $linea,
        TipoAjuste $tipo,
        string $cantidad,
        EstadoLegal $estado,
        MotivoAjuste $motivo,
        string $descripcion,
        ?int $movimientoCorregidoId,
        ?string $ruta,
        User $usuario,
    ): Ajuste {
        // Orden de bloqueo igual al cierre del aforo: línea → bulto
        $linea = BultoDetalle::query()->whereKey($linea->getKey())->with('producto:id,sku,unidad_medida')->lockForUpdate()->firstOrFail();
        $bulto = Bulto::query()->whereKey($linea->bulto_id)->with('pallet:id,codigo,estado,bodega_id')->lockForUpdate()->firstOrFail();
        $linea->setRelation('bulto', $bulto);

        $prefijo = "{$bulto->codigo} · {$linea->producto->sku}: ";

        if ($bulto->estado === EstadoBulto::ANULADO) {
            throw new ReglaNegocioException(CodigoError::BULTO_ANULADO, $prefijo.CodigoError::BULTO_ANULADO->mensaje(), ['bulto' => $bulto->codigo]);
        }
        if ($bulto->estado === EstadoBulto::EN_AFORO && ! self::permitidoEnAforo($tipo, $estado)) {
            throw new ReglaNegocioException(CodigoError::AJUSTE_LINEA_EN_AFORO, $prefijo.CodigoError::AJUSTE_LINEA_EN_AFORO->mensaje(), ['bulto' => $bulto->codigo]);
        }

        if ($movimientoCorregidoId) {
            $corregido = Movimiento::query()->find($movimientoCorregidoId)
                ?? throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, 'El movimiento que corrige no existe.', campo: 'movimiento_corregido_id');

            if ((int) $corregido->bulto_detalle_id !== (int) $linea->id) {
                throw new ReglaNegocioException(CodigoError::AJUSTE_REFERENCIA_OTRA_LINEA, datos: [
                    'movimiento' => $corregido->id, 'linea' => $linea->id,
                ], campo: 'movimiento_corregido_id');
            }
        }

        try {
            $linea->ajustar($tipo, $cantidad, $estado);   // dominio: J2, J3, UND entero
        } catch (ReglaNegocioException $e) {
            throw new ReglaNegocioException($e->codigo, $prefijo.$e->getMessage(), $e->datos, $e->campo);
        }

        $linea->save();

        $consecutivo = $this->consecutivos->ajuste();

        $ajuste = Ajuste::create([
            'consecutivo'             => $consecutivo,
            'bulto_detalle_id'        => $linea->id,
            'tipo'                    => $tipo,
            'estado_afectado'         => $estado,
            'cantidad'                => $cantidad,
            'motivo'                  => $motivo,
            'descripcion'             => $descripcion,
            'soporte_path'            => $ruta,
            'movimiento_corregido_id' => $movimientoCorregidoId,
            'usuario_id'              => $usuario->getKey(),
        ]);

        $bodegaId = $bulto->pallet?->bodega_id;

        $this->movimientos->ajustar($linea, $tipo, $cantidad, $bodegaId, $estado, $usuario, $consecutivo);

        if ($bodegaId !== null) {
            $this->ocupacion->recalcular((int) $bodegaId);
        }

        activity('ajustes')->performedOn($ajuste)->causedBy($usuario)
            ->withProperties([
                'consecutivo'  => $consecutivo,
                'bulto'        => $bulto->codigo,
                'sku'          => $linea->producto->sku,
                'tipo'         => $tipo->value,
                'estado'       => $estado->value,
                'cantidad'     => $ajuste->cantidad,
                'motivo'       => $motivo->value,
                'corrige'      => $movimientoCorregidoId,
                'resultado'    => ['recibida' => $linea->cantidad_recibida, 'legalizada' => $linea->cantidad_legalizada],
                'ip'           => request()->ip(),
            ])
            ->log('ajuste registrado');

        return $ajuste;
    }
}

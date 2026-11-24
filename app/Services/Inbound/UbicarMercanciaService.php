<?php

namespace App\Services\Inbound;

use App\Enums\CodigoError;
use App\Enums\EstadoBl;
use App\Enums\EstadoBulto;
use App\Enums\EstadoLegal;
use App\Exceptions\ReglaNegocioException;
use App\Models\Bl;
use App\Models\Bodega;
use App\Models\Pallet;
use App\Models\Ubicacion;
use App\Models\User;
use App\Services\Nucleo\MovimientoService;
use App\Services\Nucleo\OcupacionService;
use App\Support\Milesimas;
use Illuminate\Support\Facades\DB;

/**
 * CUS-008 Ubicar y trasladar (app del Operario). El nombre evita el choque con el modelo Ubicacion.
 *
 * Flujo RS-38 / D11: escanear el pallet → elegir el destino → escanear la etiqueta del destino.
 * Solo se guarda si lo escaneado coincide con lo elegido (bodega: su código; ubicación: "{BODEGA}/{UBICACION}").
 *
 * Inventario: mientras el BL está EN_RECEPCION no hay movimientos (la recepción los registra con la bodega del
 * pallet al finalizar, N11). Con el BL ya recibido, cambiar de bodega registra TRASLADO por cada línea y estado
 * legal (origen null = primera ubicación, N3). Mover dentro de la misma bodega solo cambia la ubicación.
 */
final class UbicarMercanciaService
{
    private const BL_CON_INVENTARIO = [EstadoBl::RECIBIDO, EstadoBl::DESPACHADO_PARCIAL, EstadoBl::CERRADO];

    public function __construct(
        private readonly MovimientoService $movimientos,
        private readonly OcupacionService $ocupacion,
    ) {}

    /**
     * @param array{pallet: string, bodega: string, ubicacion?: ?string, escaneo: string, referencia?: ?string} $datos
     * @return array{pallet: Pallet, operacion: string, bodega_origen: ?Bodega, bodega: Bodega, alerta_sobrecupo: bool}
     */
    public function ubicar(array $datos, User $usuario): array
    {
        return DB::transaction(function () use ($datos, $usuario) {
            $codigoPallet = mb_strtoupper(trim($datos['pallet']));
            $blId = Pallet::query()->where('codigo', $codigoPallet)->value('bl_id')
                ?? throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, "No existe el pallet {$codigoPallet}.", ['codigo' => $codigoPallet]);

            // D8: FOR SHARE sobre el BL (como bultos y pallets); finalizar toma FOR UPDATE y no lee una bodega a medio cambiar
            $bl = Bl::query()->whereKey($blId)->sharedLock()->firstOrFail();
            $pallet = Pallet::query()->where('codigo', $codigoPallet)->lockForUpdate()->firstOrFail();
            $pallet->setRelation('bl', $bl);

            $bodega = Bodega::query()->where('codigo', $datos['bodega'])->first()
                ?? throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, "No existe la bodega {$datos['bodega']}.", ['codigo' => $datos['bodega']]);

            $ubicacion = null;
            if (filled($datos['ubicacion'] ?? null)) {
                $ubicacion = $bodega->ubicaciones()->where('codigo', $datos['ubicacion'])->first()
                    ?? throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, "La bodega {$bodega->codigo} no tiene la ubicación {$datos['ubicacion']}.");
            }

            $this->exigirEscaneoCoincide($bodega, $ubicacion, $datos['escaneo']);

            // U7: no se mueve mercancía que se está contando
            if ($pallet->bultos()->where('estado', EstadoBulto::EN_AFORO->value)->exists()) {
                throw new ReglaNegocioException(CodigoError::PALLET_EN_AFORO, datos: ['pallet' => $pallet->codigo]);
            }

            $origenId = $pallet->ubicar($bodega, $ubicacion, $datos['referencia'] ?? null);   // dominio: CERRADO/UBICADO, activos, RS-17
            $pallet->save();

            foreach ($pallet->bultos()->where('estado', '!=', EstadoBulto::ANULADO->value)->lockForUpdate()->get() as $bulto) {
                $bulto->marcarUbicado();
                $bulto->save();
            }

            $cambiaDeBodega = $origenId !== $bodega->id;

            if ($cambiaDeBodega && in_array($bl->estado, self::BL_CON_INVENTARIO, true)) {
                $this->trasladarInventario($pallet, $origenId, $bodega->id, $usuario);
            }

            $destino = $this->ocupacion->recalcular($bodega);
            $origen = $cambiaDeBodega && $origenId ? $this->ocupacion->recalcular($origenId) : null;

            $operacion = $origenId === null ? 'UBICACION' : 'TRASLADO';

            activity('inbound')->performedOn($pallet)->causedBy($usuario)
                ->withProperties([
                    'pallet'    => $pallet->codigo,
                    'origen'    => $origen?->codigo,
                    'destino'   => $bodega->codigo,
                    'ubicacion' => $ubicacion?->codigo,
                    'ip'        => request()->ip(),
                ])
                ->log($operacion === 'UBICACION' ? 'pallet ubicado' : 'pallet trasladado');

            return [
                'pallet'           => $pallet->load(['bodega', 'ubicacion', 'bultos']),
                'operacion'        => $operacion,
                'bodega_origen'    => $origen,
                'bodega'           => $destino,
                'alerta_sobrecupo' => (float) $destino->porcentaje_ocupacion > 100,   // U6: se advierte, no se bloquea
            ];
        });
    }

    /** RS-38: el escaneo del destino debe coincidir con el destino elegido. */
    private function exigirEscaneoCoincide(Bodega $bodega, ?Ubicacion $ubicacion, string $escaneo): void
    {
        $esperado = $ubicacion ? "{$bodega->codigo}/{$ubicacion->codigo}" : $bodega->codigo;

        if (mb_strtoupper(trim($escaneo)) !== mb_strtoupper($esperado)) {
            throw new ReglaNegocioException(
                CodigoError::ESCANEO_NO_COINCIDE,
                "Escaneó {$escaneo}, pero el destino elegido es {$esperado}.",
                ['escaneado' => $escaneo, 'esperado' => $esperado],
            );
        }
    }

    /** Por cada línea, lo que está en cuarentena y lo legalizado aún en bodega (legalizado − despachado). */
    private function trasladarInventario(Pallet $pallet, ?int $origenId, int $destinoId, User $usuario): void
    {
        $lineas = $pallet->bultos()
            ->where('estado', '!=', EstadoBulto::ANULADO->value)
            ->with('detalles')
            ->get()
            ->flatMap->detalles;

        foreach ($lineas as $linea) {
            $porEstado = [
                EstadoLegal::CUARENTENA->value => Milesimas::de($linea->cantidad_recibida) - Milesimas::de($linea->cantidad_legalizada),
                EstadoLegal::LEGALIZADA->value => Milesimas::de($linea->cantidad_legalizada) - Milesimas::de($linea->cantidad_despachada),
            ];

            foreach ($porEstado as $estado => $milesimas) {
                if ($milesimas > 0) {
                    $this->movimientos->trasladar(
                        $linea, $origenId, $destinoId, Milesimas::texto($milesimas), EstadoLegal::from($estado), $usuario, $pallet->codigo,
                    );
                }
            }
        }
    }
}

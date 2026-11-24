<?php

namespace App\Services\Inbound;

use App\Enums\CodigoError;
use App\Enums\EstadoAforo;
use App\Enums\EstadoBl;
use App\Enums\EstadoBulto;
use App\Enums\EstadoLegal;
use App\Enums\TipoMovimiento;
use App\Exceptions\ReglaNegocioException;
use App\Exports\DiferenciasAforoExport;
use App\Models\Aforo;
use App\Models\AforoDetalle;
use App\Models\Bl;
use App\Models\Bulto;
use App\Models\BultoDetalle;
use App\Models\Producto;
use App\Models\Serial;
use App\Models\User;
use App\Services\Nucleo\ConsecutivoService;
use App\Services\Nucleo\MovimientoService;
use App\Support\Milesimas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * CUS-009 Aforo / inventario detallado (D1, D2, D7b, A1–A9).
 *
 *   Supervisor (web)  activar → [Operario (app) conteos y seriales] → cerrar | cancelar
 *
 * - Solo BL RECIBIDO o DESPACHADO_PARCIAL: antes de finalizar no hay inventario que corregir (D3).
 * - Solo bultos UBICADOS (A3), sin unidades reservadas (A4), un aforo EN_PROCESO por BL (A2).
 * - Seriales solo en UND, opcionales por línea, tantos como la cantidad contada (RS-44, A6).
 * - Cerrar aplica cada conteo: BultoDetalle::aplicarConteo + movimiento AFORO en CUARENTENA (D2).
 *   Si el conteo queda por debajo de lo legalizado, nada se aplica (CONTEO_MENOR_A_LEGALIZADO → ajuste CUS-019).
 * - El reporte de diferencias (PDF + Excel) se genera al cerrar y no cambia (A8).
 * - Cancelar no aplica los conteos y borra los seriales generados (A7); todo queda en la bitácora.
 *
 * Concurrencia: varios operarios cuentan a la vez (FOR SHARE sobre el aforo, FOR UPDATE sobre la línea);
 * cerrar y cancelar toman el aforo FOR UPDATE y esperan a que terminen los conteos en curso.
 */
final class AforoService
{
    public const BL_AFORABLE = [EstadoBl::RECIBIDO, EstadoBl::DESPACHADO_PARCIAL];

    /** Tope de seriales por línea en una sola petición. */
    public const MAX_SERIALES = 5000;

    public function __construct(
        private readonly MovimientoService $movimientos,
        private readonly ConsecutivoService $consecutivos,
    ) {}

    public static function serializable(Producto $producto): bool
    {
        $unidad = $producto->unidad_medida;

        return ($unidad instanceof \BackedEnum ? $unidad->value : (string) $unidad) === 'UND';
    }

    public static function esAforable(Bl $bl): bool
    {
        return in_array($bl->estado, self::BL_AFORABLE, true);
    }

    /** Bultos que el Supervisor puede elegir (pantalla de activar). */
    public function bultosAforables(Bl $bl): Collection
    {
        return Bulto::query()
            ->where('bl_id', $bl->id)
            ->where('estado', EstadoBulto::UBICADO->value)
            ->with([
                'detalles.producto:id,sku,descripcion,unidad_medida',
                'pallet:id,codigo,bodega_id,ubicacion_id',
                'pallet.bodega:id,codigo',
                'pallet.ubicacion:id,codigo',
            ])
            ->orderBy('codigo')
            ->get();
    }

    // ================================================================ activar (web)

    /** @param array<int, int|string> $bultoIds */
    public function activar(Bl $bl, array $bultoIds, User $usuario): Aforo
    {
        return DB::transaction(function () use ($bl, $bultoIds, $usuario) {
            $bl = Bl::query()->whereKey($bl->getKey())->lockForUpdate()->firstOrFail();

            if (! self::esAforable($bl)) {
                throw new ReglaNegocioException(
                    CodigoError::BL_ESTADO_INVALIDO,
                    "El BL {$bl->numero_bl} está {$bl->estado->value}: solo se afora un BL recibido (o con despacho parcial).",
                    ['bl' => $bl->numero_bl, 'estado' => $bl->estado->value],
                );
            }

            if ($bl->aforos()->where('estado', EstadoAforo::EN_PROCESO->value)->exists()) {
                throw new ReglaNegocioException(CodigoError::AFORO_EN_PROCESO_EXISTENTE, datos: ['bl' => $bl->numero_bl]);
            }

            $ids = array_values(array_unique(array_map('intval', $bultoIds)));

            if ($ids === []) {
                throw new ReglaNegocioException(CodigoError::AFORO_SIN_BULTOS, campo: 'bultos');
            }

            $bultos = Bulto::query()->whereIn('id', $ids)->with('detalles')->lockForUpdate()->get();

            if ($bultos->count() !== count($ids)) {
                throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, 'Alguno de los bultos elegidos no existe.', campo: 'bultos');
            }

            foreach ($bultos as $bulto) {
                $this->exigirAforable($bl, $bulto);
            }

            $aforo = Aforo::create(['bl_id' => $bl->id]);

            foreach ($bultos as $bulto) {
                $bulto->iniciarAforo();
                $bulto->save();

                foreach ($bulto->detalles as $linea) {
                    AforoDetalle::create([
                        'aforo_id'            => $aforo->id,
                        'bulto_detalle_id'    => $linea->id,
                        'cantidad_manifiesto' => $linea->cantidad_recibida,
                    ]);
                }
            }

            activity('inbound')->performedOn($aforo)->causedBy($usuario)
                ->withProperties(['bl' => $bl->numero_bl, 'bultos' => $bultos->pluck('codigo')->all(), 'ip' => request()->ip()])
                ->log('aforo activado');

            return $aforo->refresh();
        });
    }

    private function exigirAforable(Bl $bl, Bulto $bulto): void
    {
        if ($bulto->bl_id !== $bl->id) {
            throw new ReglaNegocioException(CodigoError::BULTO_OTRO_BL, "El bulto {$bulto->codigo} no es del BL {$bl->numero_bl}.", ['bulto' => $bulto->codigo], campo: 'bultos');
        }

        if ($bulto->estado !== EstadoBulto::UBICADO) {
            throw new ReglaNegocioException(
                CodigoError::BULTO_ESTADO_INVALIDO,
                "El bulto {$bulto->codigo} está {$bulto->estado->value}: solo entran al aforo bultos UBICADOS.",
                ['bulto' => $bulto->codigo, 'estado' => $bulto->estado->value],
                campo: 'bultos',
            );
        }

        if ($bulto->detalles->contains(fn (BultoDetalle $d) => Milesimas::de($d->cantidad_reservada) > 0)) {
            throw new ReglaNegocioException(CodigoError::AFORO_LINEA_RESERVADA, datos: ['bulto' => $bulto->codigo], campo: 'bultos');
        }
    }

    // ================================================================ conteo y seriales (app)

    /** Registra o corrige el conteo de una línea. Si cambia y la línea tenía seriales, se descartan (hay que regenerarlos). */
    public function registrarConteo(Aforo $aforo, int $detalleId, string $cantidad, User $usuario): AforoDetalle
    {
        return DB::transaction(function () use ($aforo, $detalleId, $cantidad, $usuario) {
            [$aforo, $detalle] = $this->bloquearDetalle($aforo, $detalleId);

            $producto = $detalle->bultoDetalle->producto;

            if (self::serializable($producto) && Milesimas::de($cantidad) % 1000 !== 0) {
                throw new ReglaNegocioException(CodigoError::CANTIDAD_INVALIDA, "{$producto->sku} se maneja en unidades: el conteo debe ser un número entero.", campo: 'cantidad');
            }

            $anterior = $detalle->cantidad_contada;
            $detalle->registrarConteo($cantidad);   // dominio: aforo EN_PROCESO, no negativo

            $descartados = 0;
            if ($anterior !== null && Milesimas::de($anterior) !== Milesimas::de($detalle->cantidad_contada)) {
                $descartados = Serial::query()->where('bulto_detalle_id', $detalle->bulto_detalle_id)->delete();
            }

            $detalle->save();

            activity('inbound')->performedOn($aforo)->causedBy($usuario)
                ->withProperties([
                    'bulto'               => $detalle->bultoDetalle->bulto->codigo,
                    'sku'                 => $producto->sku,
                    'anterior'            => $anterior,
                    'contada'             => $detalle->cantidad_contada,
                    'seriales_descartados'=> $descartados,
                    'ip'                  => request()->ip(),
                ])
                ->log('aforo conteo');

            return $detalle;
        });
    }

    /**
     * Genera un serial SKU-0000 por cada unidad contada (reemplaza los que hubiera en la línea).
     *
     * @return array{detalle: AforoDetalle, seriales: Collection<int, Serial>}
     */
    public function generarSeriales(Aforo $aforo, int $detalleId, User $usuario): array
    {
        return DB::transaction(function () use ($aforo, $detalleId, $usuario) {
            [$aforo, $detalle] = $this->bloquearDetalle($aforo, $detalleId);

            $linea = $detalle->bultoDetalle;
            $producto = $linea->producto;

            if ($detalle->cantidad_contada === null) {
                throw new ReglaNegocioException(CodigoError::CONTEO_REQUERIDO, datos: ['detalle_id' => $detalle->id]);
            }

            if (! self::serializable($producto)) {
                throw new ReglaNegocioException(CodigoError::SERIAL_SOLO_UND, datos: ['sku' => $producto->sku]);
            }

            $unidades = intdiv(Milesimas::de($detalle->cantidad_contada), 1000);

            if ($unidades <= 0 || $unidades > self::MAX_SERIALES) {
                throw new ReglaNegocioException(CodigoError::CANTIDAD_INVALIDA, 'La línea debe tener entre 1 y '.self::MAX_SERIALES.' unidades contadas para serializar.');
            }

            Serial::query()->where('bulto_detalle_id', $linea->id)->delete();

            $seriales = collect(range(1, $unidades))->map(fn () => Serial::create([
                'codigo'           => $this->consecutivos->serial($producto->sku),
                'bulto_detalle_id' => $linea->id,
            ]));

            activity('inbound')->performedOn($aforo)->causedBy($usuario)
                ->withProperties(['bulto' => $linea->bulto->codigo, 'sku' => $producto->sku, 'seriales' => $unidades, 'ip' => request()->ip()])
                ->log('aforo seriales');

            return ['detalle' => $detalle, 'seriales' => $seriales];
        });
    }

    /** Etiqueta ZPL 4×2 in del serial (misma plantilla que CUS-007). */
    public function zplSerial(Serial $serial, string $sku, string $bulto): string
    {
        $limpio = fn (string $t) => str_replace(['^', '~'], ' ', $t);

        return "^XA\n^CI28\n^PW812\n^LL406\n^LH0,0\n"
            .'^FO30,20^A0N,30,30^FDSERIAL '.$limpio($sku)."^FS\n"
            .'^FO30,60^BY3,2,150^BCN,150,Y,N,N^FD'.$limpio($serial->codigo)."^FS\n"
            .'^FO30,262^A0N,26,26^FB752,1,0,L^FDBulto '.$limpio($bulto)."^FS\n"
            ."^XZ\n";
    }

    /** @return array{0: Aforo, 1: AforoDetalle} */
    private function bloquearDetalle(Aforo $aforo, int $detalleId): array
    {
        $aforo = Aforo::query()->whereKey($aforo->getKey())->sharedLock()->firstOrFail();
        $aforo->exigirEnProceso();

        $detalle = AforoDetalle::query()->where('aforo_id', $aforo->id)->whereKey($detalleId)->lockForUpdate()->first()
            ?? throw new ReglaNegocioException(CodigoError::DETALLE_OTRO_AFORO, datos: ['detalle_id' => $detalleId], campo: 'detalle_id');

        $detalle->setRelation('aforo', $aforo);
        $detalle->load(['bultoDetalle.producto:id,sku,descripcion,unidad_medida', 'bultoDetalle.bulto:id,codigo']);

        return [$aforo, $detalle];
    }

    // ================================================================ cerrar y cancelar (web)

    public function cerrar(Aforo $aforo, User $usuario): Aforo
    {
        return DB::transaction(function () use ($aforo, $usuario) {
            $aforo = Aforo::query()->whereKey($aforo->getKey())->lockForUpdate()->firstOrFail();
            $aforo->cerrar();   // dominio: EN_PROCESO y todas las líneas contadas

            $detalles = $aforo->detalles()->get();
            $lineas = BultoDetalle::query()->whereIn('id', $detalles->pluck('bulto_detalle_id'))
                ->with(['bulto.pallet:id,bodega_id', 'producto:id,sku'])
                ->lockForUpdate()->get()->keyBy('id');

            $serialesPorLinea = Serial::query()->whereIn('bulto_detalle_id', $lineas->keys())
                ->selectRaw('bulto_detalle_id, count(*) as total')->groupBy('bulto_detalle_id')
                ->pluck('total', 'bulto_detalle_id');

            $referencia = "AFO-{$aforo->id}";

            foreach ($detalles as $detalle) {
                $linea = $lineas[$detalle->bulto_detalle_id];
                $contada = Milesimas::de($detalle->cantidad_contada);
                $seriales = (int) ($serialesPorLinea[$linea->id] ?? 0);

                if ($seriales > 0 && $seriales * 1000 !== $contada) {
                    throw new ReglaNegocioException(CodigoError::SERIALES_INCOMPLETOS, datos: [
                        'bulto' => $linea->bulto->codigo, 'sku' => $linea->producto->sku,
                        'seriales' => $seriales, 'contada' => $detalle->cantidad_contada,
                    ]);
                }

                $diferencia = $linea->aplicarConteo($detalle->cantidad_contada);   // dominio: > 0 y ≥ legalizado (A4)

                if ($seriales > 0) {
                    $linea->serializado = true;
                    $this->legalizarSeriales($linea);
                }

                $linea->save();

                if (Milesimas::de($diferencia) !== 0) {
                    $this->movimientos->registrar(
                        TipoMovimiento::AFORO, $linea, $diferencia, $linea->bulto->pallet?->bodega_id, EstadoLegal::CUARENTENA, $usuario, $referencia,
                    );
                }
            }

            foreach ($this->bultosDelAforo($aforo) as $bulto) {
                $bulto->cerrarAforo();
                $bulto->save();
            }

            $aforo->save();
            $aforo->reporte_path = $this->generarReporte($aforo);
            $aforo->save();

            activity('inbound')->performedOn($aforo)->causedBy($usuario)
                ->withProperties(['bl' => $aforo->bl->numero_bl, 'lineas' => $detalles->count(), 'reporte' => $aforo->reporte_path, 'ip' => request()->ip()])
                ->log('aforo cerrado');

            return $aforo;
        });
    }

    public function cancelar(Aforo $aforo, User $usuario): Aforo
    {
        return DB::transaction(function () use ($aforo, $usuario) {
            $aforo = Aforo::query()->whereKey($aforo->getKey())->lockForUpdate()->firstOrFail();
            $aforo->cancelar();

            $detalles = $aforo->detalles()->get(['id', 'bulto_detalle_id', 'cantidad_contada']);

            // Solo los seriales de este aforo: una línea ya serializada no vuelve a entrar (AFORADO)
            $seriales = Serial::query()
                ->whereIn('bulto_detalle_id', $detalles->pluck('bulto_detalle_id'))
                ->whereHas('bultoDetalle', fn ($q) => $q->where('serializado', false))
                ->delete();

            foreach ($this->bultosDelAforo($aforo) as $bulto) {
                $bulto->cancelarAforo();
                $bulto->save();
            }

            $aforo->save();

            activity('inbound')->performedOn($aforo)->causedBy($usuario)
                ->withProperties([
                    'bl'                   => $aforo->bl->numero_bl,
                    'conteos_descartados'  => $detalles->whereNotNull('cantidad_contada')->map(fn ($d) => [$d->bulto_detalle_id => $d->cantidad_contada])->values()->all(),
                    'seriales_descartados' => $seriales,
                    'ip'                   => request()->ip(),
                ])
                ->log('aforo cancelado');

            return $aforo;
        });
    }

    /** Los primeros N seriales quedan LEGALIZADOS si la línea ya tenía N unidades legalizadas. */
    private function legalizarSeriales(BultoDetalle $linea): void
    {
        $legalizadas = intdiv(Milesimas::de($linea->cantidad_legalizada), 1000);

        if ($legalizadas > 0) {
            Serial::query()->where('bulto_detalle_id', $linea->id)->orderBy('id')->limit($legalizadas)->get()
                ->each(function (Serial $s) {
                    $s->legalizar();
                    $s->save();
                });
        }
    }

    /** @return Collection<int, Bulto> */
    private function bultosDelAforo(Aforo $aforo): Collection
    {
        return Bulto::query()
            ->whereIn('id', BultoDetalle::query()->select('bulto_id')
                ->whereIn('id', AforoDetalle::query()->select('bulto_detalle_id')->where('aforo_id', $aforo->id)))
            ->with('pallet:id,estado')
            ->lockForUpdate()
            ->get();
    }

    /** A8: PDF y Excel en storage/app/private/aforos/AFO-{id}/; reporte_path apunta al PDF, el Excel va al lado. */
    private function generarReporte(Aforo $aforo): string
    {
        $export = new DiferenciasAforoExport($aforo);
        $carpeta = "aforos/AFO-{$aforo->id}";
        $pdf = "{$carpeta}/diferencias-aforo-{$aforo->id}.pdf";

        Storage::disk('local')->put($pdf, Pdf::loadView('reportes.aforo-diferencias', $export->datos())->setPaper('letter', 'landscape')->output());
        $export->guardar(self::rutaExcel($pdf));

        return $pdf;
    }

    public static function rutaExcel(string $rutaPdf): string
    {
        return preg_replace('/\.pdf$/', '.xlsx', $rutaPdf);
    }
}

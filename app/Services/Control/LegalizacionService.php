<?php

namespace App\Services\Control;

use App\Enums\CodigoError;
use App\Enums\EstadoBl;
use App\Enums\EstadoBulto;
use App\Enums\EstadoLegalSerial;
use App\Enums\EstadoPallet;
use App\Exceptions\ReglaNegocioException;
use App\Models\Bl;
use App\Models\Bulto;
use App\Models\BultoDetalle;
use App\Models\Legalizacion;
use App\Models\LegalizacionDetalle;
use App\Models\LegalizacionSerial;
use App\Models\Serial;
use App\Models\User;
use App\Services\Nucleo\ConsecutivoService;
use App\Services\Nucleo\MovimientoService;
use App\Support\Milesimas;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * CUS-010 Registrar Legalización (L1–L21, secuencia 3.3.2).
 *
 *   Analista/Supervisor (web)  elige BL → arma el carrito (Modos A/B/C) → autorización + soporte → confirma
 *
 * - Un solo BL RECIBIDO o DESPACHADO_PARCIAL (L2). Se excluyen bultos ANULADOS, EN_AFORO y de pallets sin ubicar (L3).
 * - Modo A: toda la cuarentena del bulto; en líneas serializadas, todos sus seriales en cuarentena (L4).
 * - Modo B: cantidad, solo líneas sin seriales; UND entero, KG/LT hasta 3 decimales (L5).
 * - Modo C: seriales; única forma de legalizar en parte una línea serializada (L6).
 * - La misma línea se consolida en una fila del detalle; el modo no se guarda (L7).
 * - El servidor no confía en el carrito: bloquea BL, líneas, bultos y seriales (FOR UPDATE) y revalida (L10, L16, D8).
 * - LEGALIZACION = 2 movimientos por línea en la bodega del pallet, ref = consecutivo (L17).
 * - Soporte privado obligatorio; si la transacción falla se borra (L14). Número repetido: solo aviso (L12).
 */
final class LegalizacionService
{
    public const BL_LEGALIZABLE = [EstadoBl::RECIBIDO, EstadoBl::DESPACHADO_PARCIAL];

    public const CARPETA = 'legalizaciones';

    public function __construct(
        private readonly MovimientoService $movimientos,
        private readonly ConsecutivoService $consecutivos,
    ) {}

    public static function esLegalizable(Bl $bl): bool
    {
        return in_array($bl->estado, self::BL_LEGALIZABLE, true);
    }

    /** L3: por qué un bulto no se puede legalizar ahora (null = se puede). */
    public static function motivoBloqueo(Bulto $bulto): ?CodigoError
    {
        return match (true) {
            $bulto->estado === EstadoBulto::ANULADO  => CodigoError::BULTO_ANULADO,
            $bulto->estado === EstadoBulto::EN_AFORO => CodigoError::BULTO_EN_AFORO,
            $bulto->pallet?->estado !== EstadoPallet::UBICADO,
            $bulto->pallet?->bodega_id === null      => CodigoError::PALLET_SIN_UBICAR,
            default                                  => null,
        };
    }

    // ================================================================ pantalla

    /** Paso 1 del asistente: BL legalizables con alguna línea en cuarentena (de bultos no anulados). */
    public function blsLegalizables(): Collection
    {
        return Bl::query()
            ->whereIn('estado', array_map(fn (EstadoBl $e) => $e->value, self::BL_LEGALIZABLE))
            ->whereHas('bultoDetalles', fn (Builder $q) => $this->enCuarentena($q)
                ->whereHas('bulto', fn (Builder $b) => $b->where('estado', '!=', EstadoBulto::ANULADO->value)))
            ->with('cliente:id,codigo_corto,razon_social')
            ->orderBy('fecha_ingreso')
            ->orderBy('id')
            ->get(['id', 'numero_bl', 'cliente_id', 'fecha_ingreso', 'estado']);
    }

    /**
     * Datos del carrito para el BL: bultos no anulados con líneas en cuarentena > 0, su bloqueo (EN_AFORO / sin ubicar),
     * cada línea con en_cuarentena, serializado y sus seriales en cuarentena; y los códigos ya legalizados (FA-01).
     *
     * @return array{bultos: array<int, array>, legalizados: array{bultos: list<string>, seriales: list<string>}}
     */
    public function cargarBl(Bl $bl): array
    {
        $bultos = Bulto::query()
            ->where('bl_id', $bl->id)
            ->where('estado', '!=', EstadoBulto::ANULADO->value)
            ->whereHas('detalles', fn (Builder $q) => $this->enCuarentena($q))
            ->with([
                'detalles'          => fn ($q) => $this->enCuarentena($q)->orderBy('id'),
                'detalles.producto' => fn ($q) => $q->select('id', 'sku', 'descripcion', 'unidad_medida'),
                'detalles.seriales' => fn ($q) => $q->where('estado_legal', EstadoLegalSerial::EN_CUARENTENA->value)
                    ->orderBy('codigo')->select('id', 'codigo', 'bulto_detalle_id'),
                'pallet:id,codigo,estado,bodega_id,ubicacion_id',
                'pallet.bodega:id,codigo',
                'pallet.ubicacion:id,codigo',
            ])
            ->orderBy('codigo')
            ->get();

        $filas = $bultos->map(function (Bulto $bulto) {
            $bloqueo = self::motivoBloqueo($bulto);

            return [
                'id'       => $bulto->id,
                'codigo'   => $bulto->codigo,
                'estado'   => $bulto->estado->value,
                'pallet'   => $bulto->pallet?->codigo,
                'lugar'    => trim(($bulto->pallet?->bodega?->codigo ?? '').($bulto->pallet?->ubicacion ? ' / '.$bulto->pallet->ubicacion->codigo : '')),
                'bloqueo'  => $bloqueo?->value,
                'motivo'   => $bloqueo?->mensaje(),
                'lineas'   => $bulto->detalles->map(fn (BultoDetalle $l) => [
                    'id'            => $l->id,
                    'bulto_id'      => $bulto->id,
                    'sku'           => $l->producto->sku,
                    'descripcion'   => $l->producto->descripcion,
                    'unidad'        => $l->producto->unidad_medida->value,
                    'recibida'      => $l->cantidad_recibida,
                    'legalizada'    => $l->cantidad_legalizada,
                    'en_cuarentena' => $l->en_cuarentena,
                    'serializado'   => (bool) $l->serializado,
                    'seriales'      => $l->seriales->map(fn (Serial $s) => ['id' => $s->id, 'codigo' => $s->codigo])->values()->all(),
                ])->values()->all(),
            ];
        })->values()->all();

        $legalizados = [
            'bultos' => Bulto::query()
                ->where('bl_id', $bl->id)
                ->where('estado', '!=', EstadoBulto::ANULADO->value)
                ->whereDoesntHave('detalles', fn (Builder $q) => $this->enCuarentena($q))
                ->orderBy('codigo')->pluck('codigo')->all(),
            'seriales' => Serial::query()
                ->where('estado_legal', EstadoLegalSerial::LEGALIZADO->value)
                ->whereHas('bultoDetalle', fn (Builder $q) => $q->where('bl_id', $bl->id))
                ->orderBy('codigo')->pluck('codigo')->all(),
        ];

        return ['bultos' => $filas, 'legalizados' => $legalizados];
    }

    /** Detalle de una LEG por bulto, producto y serial (CUS-010 show y CUS-012 H1). */
    public function cargarDetalle(Legalizacion $legalizacion): Legalizacion
    {
        return $legalizacion->load([
            'bl:id,numero_bl,cliente_id',
            'bl.cliente:id,codigo_corto,razon_social',
            'usuario:id,nombre',
            'detalles.bultoDetalle:id,bulto_id,producto_id',
            'detalles.bultoDetalle.producto:id,sku,descripcion,unidad_medida',
            'detalles.bultoDetalle.bulto:id,codigo,pallet_id',
            'detalles.bultoDetalle.bulto.pallet:id,codigo,bodega_id',
            'detalles.bultoDetalle.bulto.pallet.bodega:id,codigo',
            'detalles.seriales.serial:id,codigo',
        ]);
    }

    /** L12: otras LEG con el mismo número de autorización (sin distinguir mayúsculas). */
    public function usosDeAutorizacion(string $numero, ?int $excepto = null): Collection
    {
        $numero = trim($numero);

        if ($numero === '') {
            return collect();
        }

        return Legalizacion::query()
            ->whereRaw('upper(numero_autorizacion) = upper(?)', [$numero])
            ->when($excepto, fn ($q) => $q->whereKeyNot($excepto))
            ->with('bl:id,numero_bl')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'consecutivo', 'bl_id', 'created_at']);
    }

    // ================================================================ registrar

    /**
     * @param list<array{modo: string, bulto_id?: int, linea_id?: int, cantidad?: string, seriales?: list<int>}> $items
     * @param array{tipo_documento: string, numero_autorizacion: string, fecha_autorizacion: string} $autorizacion
     */
    public function registrar(Bl $bl, array $items, array $autorizacion, UploadedFile $soporte, User $usuario): Legalizacion
    {
        if ($items === []) {
            throw new ReglaNegocioException(CodigoError::LEGALIZACION_VACIA);
        }

        $ruta = Storage::disk('local')->putFile(self::CARPETA.'/'.now()->format('Y'), $soporte);

        if (! $ruta) {
            throw new ReglaNegocioException(CodigoError::SOPORTE_REQUERIDO, 'No se pudo guardar el soporte. Intente de nuevo.', campo: 'soporte');
        }

        try {
            return DB::transaction(fn () => $this->registrarEnTransaccion($bl, $items, $autorizacion, $ruta, $usuario));
        } catch (Throwable $e) {
            Storage::disk('local')->delete($ruta); // L14: no quedan soportes huérfanos
            throw $e;
        }
    }

    private function registrarEnTransaccion(Bl $bl, array $items, array $autorizacion, string $ruta, User $usuario): Legalizacion
    {
        $bl = Bl::query()->whereKey($bl->getKey())->lockForUpdate()->firstOrFail();

        if (! self::esLegalizable($bl)) {
            throw new ReglaNegocioException(CodigoError::BL_NO_LEGALIZABLE, datos: ['bl' => $bl->numero_bl, 'estado' => $bl->estado->value]);
        }

        // ---- 1. Bloquear y revalidar todo lo que toca el carrito (orden: BL → líneas → bultos → seriales)
        $bultoIdsA = [];
        $lineaIds = [];
        $serialIds = [];

        foreach ($items as $item) {
            match ($item['modo']) {
                'A' => $bultoIdsA[] = (int) $item['bulto_id'],
                'B' => $lineaIds[] = (int) $item['linea_id'],
                'C' => [$lineaIds[] = (int) $item['linea_id'], array_push($serialIds, ...array_map('intval', $item['seriales']))],
            };
        }

        $lineas = BultoDetalle::query()
            ->where(fn (Builder $q) => $q->whereIn('bulto_id', $bultoIdsA)->orWhereIn('id', $lineaIds))
            ->with('producto:id,sku,unidad_medida')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $bultos = Bulto::query()
            ->whereIn('id', $lineas->pluck('bulto_id')->merge($bultoIdsA)->unique()->values())
            ->with('pallet:id,codigo,estado,bodega_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach (array_unique($bultoIdsA) as $id) {
            $bultos->has($id) || throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, 'Alguno de los bultos del carrito no existe.');
        }
        foreach (array_unique($lineaIds) as $id) {
            $lineas->has($id) || throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, 'Alguna de las líneas del carrito no existe.');
        }

        foreach ($bultos as $bulto) {
            if ((int) $bulto->bl_id !== (int) $bl->id) {
                throw new ReglaNegocioException(CodigoError::LEGALIZACION_OTRO_BL, "{$bulto->codigo}: ".CodigoError::LEGALIZACION_OTRO_BL->mensaje(), ['bulto' => $bulto->codigo]);
            }
            if ($motivo = self::motivoBloqueo($bulto)) {
                throw new ReglaNegocioException($motivo, "{$bulto->codigo}: {$motivo->mensaje()}", ['bulto' => $bulto->codigo]);
            }
        }

        foreach ($lineas as $linea) {
            $linea->setRelation('bulto', $bultos[$linea->bulto_id]);
        }

        // ---- 2. Consolidar por línea (L7): cantidad para las no serializadas, seriales para las serializadas
        /** @var array<int, array{cantidad: int, todos: bool, seriales: list<int>}> $porLinea */
        $porLinea = [];
        $sumar = function (int $lineaId) use (&$porLinea): void {
            $porLinea[$lineaId] ??= ['cantidad' => 0, 'todos' => false, 'seriales' => []];
        };

        foreach ($items as $item) {
            if ($item['modo'] === 'A') {
                $bulto = $bultos[(int) $item['bulto_id']];
                $delBulto = $lineas->where('bulto_id', $bulto->id)->filter(fn (BultoDetalle $l) => Milesimas::de($l->en_cuarentena) > 0);

                if ($delBulto->isEmpty()) {
                    throw new ReglaNegocioException(CodigoError::BULTO_YA_LEGALIZADO, "{$bulto->codigo}: ".CodigoError::BULTO_YA_LEGALIZADO->mensaje(), ['bulto' => $bulto->codigo]);
                }

                foreach ($delBulto as $linea) {
                    $sumar($linea->id);

                    if ($linea->serializado) {
                        $porLinea[$linea->id]['todos'] = true;
                    } else {
                        $porLinea[$linea->id]['cantidad'] += Milesimas::de($linea->en_cuarentena);
                    }
                }

                continue;
            }

            $linea = $lineas[(int) $item['linea_id']];
            $sumar($linea->id);

            if ($item['modo'] === 'B') {
                $porLinea[$linea->id]['cantidad'] += Milesimas::de($item['cantidad']);
            } else {
                array_push($porLinea[$linea->id]['seriales'], ...array_map('intval', $item['seriales']));
            }
        }

        // ---- 3. Seriales bloqueados: los elegidos (Modo C) y los de las líneas completas (Modo A)
        $serialIds = array_values(array_unique($serialIds));
        $elegidos = Serial::query()->whereIn('id', $serialIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        if ($elegidos->count() !== count($serialIds)) {
            throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, 'Alguno de los seriales del carrito no existe.');
        }

        $completas = array_keys(array_filter($porLinea, fn (array $s) => $s['todos']));
        $enCuarentena = Serial::query()
            ->whereIn('bulto_detalle_id', $completas)
            ->where('estado_legal', EstadoLegalSerial::EN_CUARENTENA->value)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        // ---- 4. Registrar (L15–L19)
        $consecutivo = $this->consecutivos->legalizacion();

        $legalizacion = Legalizacion::create([
            'consecutivo'         => $consecutivo,
            'bl_id'               => $bl->id,
            'tipo_documento'      => $autorizacion['tipo_documento'],
            'numero_autorizacion' => $autorizacion['numero_autorizacion'],
            'fecha_autorizacion'  => $autorizacion['fecha_autorizacion'],
            'soporte_path'        => $ruta,
            'usuario_id'          => $usuario->getKey(),
        ]);

        $unidades = 0;
        $totalSeriales = 0;

        foreach ($porLinea as $lineaId => $seleccion) {
            $linea = $lineas[$lineaId];
            $bulto = $linea->bulto;
            $seriales = collect();

            if ($linea->serializado && $seleccion['cantidad'] > 0) {
                throw new ReglaNegocioException(CodigoError::LINEA_SERIALIZADA, $this->prefijo($linea).CodigoError::LINEA_SERIALIZADA->mensaje(), ['linea' => $linea->id]);
            }

            if ($linea->serializado || $seleccion['seriales'] !== []) {
                $seriales = $enCuarentena->where('bulto_detalle_id', $linea->id)->keyBy('id');

                foreach ($seleccion['seriales'] as $id) {
                    $seriales->put($id, $elegidos[$id]);
                }

                $cantidad = $this->conContexto($linea, fn () => $linea->legalizarSeriales($seriales->values()));
            } else {
                $cantidad = $this->conContexto($linea, fn () => $linea->legalizarPorCantidad(Milesimas::texto($seleccion['cantidad'])));
            }

            $linea->save();

            $detalle = LegalizacionDetalle::create([
                'legalizacion_id'  => $legalizacion->id,
                'bulto_detalle_id' => $linea->id,
                'cantidad'         => $cantidad,
            ]);

            if ($seriales->isNotEmpty()) {
                $ids = $seriales->keys()->all();

                Serial::query()->whereIn('id', $ids)->update([
                    'estado_legal' => EstadoLegalSerial::LEGALIZADO->value,
                    'updated_at'   => now(),
                ]);

                foreach (array_chunk($ids, 500) as $lote) {
                    LegalizacionSerial::insert(array_map(fn (int $id) => [
                        'legalizacion_detalle_id' => $detalle->id,
                        'serial_id'               => $id,
                        'created_at'              => now(),
                    ], $lote));
                }

                $totalSeriales += count($ids);
            }

            $this->movimientos->legalizar($linea, $cantidad, (int) $bulto->pallet->bodega_id, $usuario, $consecutivo);
            $unidades += Milesimas::de($cantidad);
        }

        activity('legalizacion')->performedOn($legalizacion)->causedBy($usuario)
            ->withProperties([
                'consecutivo'         => $consecutivo,
                'bl'                  => $bl->numero_bl,
                'tipo_documento'      => $legalizacion->tipo_documento->value,
                'numero_autorizacion' => $legalizacion->numero_autorizacion,
                'lineas'              => count($porLinea),
                'unidades'            => Milesimas::texto($unidades),
                'seriales'            => $totalSeriales,
                'ip'                  => request()->ip(),
            ])
            ->log('legalización registrada');

        return $legalizacion;
    }

    // ================================================================ apoyo

    /** Línea con cuarentena > 0 (recibida > legalizada). Sirve para whereHas (Builder) y para with (Relation). */
    private function enCuarentena($q)
    {
        return $q->whereColumn('cantidad_recibida', '>', 'cantidad_legalizada');
    }

    private function prefijo(BultoDetalle $linea): string
    {
        return "{$linea->bulto->codigo} · {$linea->producto->sku}: ";
    }

    /** Las reglas del dominio no conocen el bulto: se antepone "BUL-… · SKU:" al mensaje para el usuario. */
    private function conContexto(BultoDetalle $linea, Closure $accion): string
    {
        try {
            return $accion();
        } catch (ReglaNegocioException $e) {
            throw new ReglaNegocioException($e->codigo, $this->prefijo($linea).$e->getMessage(), $e->datos, $e->campo);
        }
    }
}

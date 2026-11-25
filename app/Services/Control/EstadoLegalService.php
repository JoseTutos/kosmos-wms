<?php

namespace App\Services\Control;

use App\Enums\EstadoAforo;
use App\Enums\EstadoBl;
use App\Enums\EstadoBulto;
use App\Models\Bl;
use App\Models\BultoDetalle;
use App\Support\Milesimas;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CUS-011 Estado Legal Activo (C1–C4, RS-50). Es una consulta: se actualiza sola (L20).
 *
 * Una sola consulta agregada (sin N+1): BL RECIBIDO o DESPACHADO_PARCIAL con cuarentena > 0 en bultos no anulados,
 * con totales recibida/legalizada/cuarentena, días desde el ingreso (zona de la operación) y si tiene aforo en proceso.
 * El BL 100 % legalizado no aparece.
 */
final class EstadoLegalService
{
    public const ESTADOS_BL = [EstadoBl::RECIBIDO, EstadoBl::DESPACHADO_PARCIAL];

    public const INDICADOR_TOTAL = 'total';      // rojo: nada legalizado

    public const INDICADOR_PARCIAL = 'parcial';  // amarillo

    public const INDICADOR_AFORO = 'aforo';      // azul: hay un aforo en proceso

    /** @param array{cliente_id?: int|string|null, bl?: string|null, dias_min?: int|string|null, dias_max?: int|string|null} $filtros */
    public function consulta(array $filtros = []): Builder
    {
        $hoy = self::hoy();
        $dias = '(?::date - bls.fecha_ingreso)';

        return $this->base()
            ->whereIn('bls.estado', array_map(fn (EstadoBl $e) => $e->value, self::ESTADOS_BL))
            ->where('t.total_cuarentena', '>', 0)   // RS-50: el BL 100 % legalizado no aparece
            ->when($filtros['cliente_id'] ?? null, fn ($q, $id) => $q->where('bls.cliente_id', (int) $id))
            ->when(trim((string) ($filtros['bl'] ?? '')), fn ($q, $texto) => $q->where('bls.numero_bl', 'ilike', '%'.mb_substr($texto, 0, 30).'%'))
            ->when(is_numeric($filtros['dias_min'] ?? null), fn ($q) => $q->whereRaw("{$dias} >= ?", [$hoy, (int) $filtros['dias_min']]))
            ->when(is_numeric($filtros['dias_max'] ?? null), fn ($q) => $q->whereRaw("{$dias} <= ?", [$hoy, (int) $filtros['dias_max']]))
            ->orderBy('bls.fecha_ingreso')   // C3: más días primero
            ->orderBy('bls.id');
    }

    /** BL con sus totales (bultos no anulados), días desde el ingreso y aforo en proceso. Sin filtros. */
    private function base(): Builder
    {
        $totales = DB::table('bulto_detalles as bd')
            ->join('bultos as b', 'b.id', '=', 'bd.bulto_id')
            ->where('b.estado', '!=', EstadoBulto::ANULADO->value)
            ->groupBy('bd.bl_id')
            ->select('bd.bl_id')
            ->selectRaw('SUM(bd.cantidad_recibida) AS total_recibida')
            ->selectRaw('SUM(bd.cantidad_legalizada) AS total_legalizada')
            ->selectRaw('SUM(bd.cantidad_recibida - bd.cantidad_legalizada) AS total_cuarentena');

        return Bl::query()
            ->joinSub($totales, 't', 't.bl_id', '=', 'bls.id')
            ->select('bls.*', 't.total_recibida', 't.total_legalizada', 't.total_cuarentena')
            ->selectRaw('(?::date - bls.fecha_ingreso) AS dias', [self::hoy()])
            ->selectRaw('EXISTS (SELECT 1 FROM aforos a WHERE a.bl_id = bls.id AND a.estado = ?) AS en_aforo', [EstadoAforo::EN_PROCESO->value])
            ->with('cliente:id,codigo_corto,razon_social');
    }

    /** C2: rojo si la cuarentena es total, amarillo si es parcial, azul si el BL está en aforo. */
    public static function indicador(Bl $fila): string
    {
        if ((bool) $fila->en_aforo) {
            return self::INDICADOR_AFORO;
        }

        return Milesimas::de((string) $fila->total_legalizada) === 0 ? self::INDICADOR_TOTAL : self::INDICADOR_PARCIAL;
    }

    /** C4: detalle por bulto y producto (bultos no anulados), con cuarentena y legalizada. */
    public function detalle(Bl $bl): Collection
    {
        return BultoDetalle::query()
            ->where('bulto_detalles.bl_id', $bl->id)
            ->whereHas('bulto', fn ($q) => $q->where('estado', '!=', EstadoBulto::ANULADO->value))
            ->with([
                'bulto:id,codigo,estado,pallet_id',
                'bulto.pallet:id,codigo,bodega_id,ubicacion_id',
                'bulto.pallet.bodega:id,codigo',
                'bulto.pallet.ubicacion:id,codigo',
                'producto:id,sku,descripcion,unidad_medida',
            ])
            ->join('bultos', 'bultos.id', '=', 'bulto_detalles.bulto_id')
            ->orderBy('bultos.codigo')
            ->orderBy('bulto_detalles.id')
            ->select('bulto_detalles.*')
            ->get();
    }

    /** Totales del BL para la cabecera del detalle (null si no tiene bultos). */
    public function totales(Bl $bl): ?Bl
    {
        return $this->base()->where('bls.id', $bl->id)->first();
    }

    public static function hoy(): string
    {
        return CarbonImmutable::now(config('kosmos.zona_horaria', 'America/Bogota'))->toDateString();
    }
}

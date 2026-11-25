<?php

namespace App\Services\Control;

use App\Models\Legalizacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * CUS-012 Históricos y Auditoría (H1–H3, RS-51, RS-52). Solo lectura: nada se borra ni se purga.
 * Filtros: rango de fechas (de registro), cliente, BL y número de autorización.
 * La lista y los exportes usan exactamente los mismos filtros.
 */
final class HistoricoLegalizacionService
{
    /** @param array{desde?: ?string, hasta?: ?string, cliente_id?: mixed, bl?: ?string, numero?: ?string} $filtros */
    public function consulta(array $filtros = []): Builder
    {
        $resumen = DB::table('legalizacion_detalles as ld')
            ->join('bulto_detalles as bd', 'bd.id', '=', 'ld.bulto_detalle_id')
            ->leftJoin('legalizacion_seriales as ls', 'ls.legalizacion_detalle_id', '=', 'ld.id')
            ->groupBy('ld.legalizacion_id')
            ->select('ld.legalizacion_id')
            ->selectRaw('COUNT(DISTINCT bd.bulto_id) AS bultos')
            ->selectRaw('COUNT(DISTINCT ld.id) AS lineas')
            ->selectRaw('COUNT(ls.id) AS seriales');

        $unidades = DB::query()
            ->fromSub(DB::table('legalizacion_detalles as ld')
                ->join('bulto_detalles as bd', 'bd.id', '=', 'ld.bulto_detalle_id')
                ->join('productos as p', 'p.id', '=', 'bd.producto_id')
                ->groupBy('ld.legalizacion_id', 'p.unidad_medida')
                ->select('ld.legalizacion_id', 'p.unidad_medida')
                ->selectRaw('SUM(ld.cantidad) AS total'), 'x')
            ->groupBy('x.legalizacion_id')
            ->select('x.legalizacion_id')
            ->selectRaw("string_agg(x.total::text || ' ' || x.unidad_medida, ' · ' ORDER BY x.unidad_medida) AS unidades");

        return $this->filtrar(Legalizacion::query(), $filtros)
            ->leftJoinSub($resumen, 'r', 'r.legalizacion_id', '=', 'legalizaciones.id')
            ->leftJoinSub($unidades, 'u', 'u.legalizacion_id', '=', 'legalizaciones.id')
            ->select('legalizaciones.*', 'r.bultos', 'r.lineas', 'r.seriales', 'u.unidades')
            ->with(['bl:id,numero_bl,cliente_id', 'bl.cliente:id,codigo_corto,razon_social', 'usuario:id,nombre'])
            ->orderByDesc('legalizaciones.created_at')
            ->orderByDesc('legalizaciones.id');
    }

    /**
     * Filas para Excel/CSV: una por línea legalizada (bulto · producto), con sus seriales.
     *
     * @return QueryBuilder  ordenada por LEG y línea, lista para lazy()
     */
    public function filasExporte(array $filtros = []): QueryBuilder
    {
        $ids = $this->filtrar(Legalizacion::query(), $filtros)->select('legalizaciones.id');

        $seriales = DB::table('legalizacion_seriales as ls')
            ->join('seriales as s', 's.id', '=', 'ls.serial_id')
            ->groupBy('ls.legalizacion_detalle_id')
            ->select('ls.legalizacion_detalle_id')
            ->selectRaw("string_agg(s.codigo, ' ' ORDER BY s.codigo) AS seriales")
            ->selectRaw('COUNT(*) AS total_seriales');

        return DB::table('legalizacion_detalles as ld')
            ->join('legalizaciones as l', 'l.id', '=', 'ld.legalizacion_id')
            ->join('bls as b', 'b.id', '=', 'l.bl_id')
            ->join('clientes as c', 'c.id', '=', 'b.cliente_id')
            ->join('users as us', 'us.id', '=', 'l.usuario_id')
            ->join('bulto_detalles as bd', 'bd.id', '=', 'ld.bulto_detalle_id')
            ->join('bultos as bu', 'bu.id', '=', 'bd.bulto_id')
            ->join('productos as p', 'p.id', '=', 'bd.producto_id')
            ->leftJoinSub($seriales, 'se', 'se.legalizacion_detalle_id', '=', 'ld.id')
            ->whereIn('l.id', $ids)
            ->select([
                'ld.id', 'l.created_at', 'l.consecutivo', 'b.numero_bl', 'c.codigo_corto', 'c.razon_social',
                'l.tipo_documento', 'l.numero_autorizacion', 'l.fecha_autorizacion', 'us.nombre as usuario',
                'bu.codigo as bulto', 'p.sku', 'p.descripcion', 'p.unidad_medida', 'ld.cantidad',
                'se.total_seriales', 'se.seriales',
            ])
            ->orderBy('l.id')
            ->orderBy('ld.id');
    }

    private function filtrar(Builder $q, array $filtros): Builder
    {
        $texto = fn (string $clave, int $max) => mb_substr(trim((string) ($filtros[$clave] ?? '')), 0, $max);

        return $q
            ->when($this->fecha($filtros['desde'] ?? null), fn ($w, $d) => $w->whereDate('legalizaciones.created_at', '>=', $d))
            ->when($this->fecha($filtros['hasta'] ?? null), fn ($w, $d) => $w->whereDate('legalizaciones.created_at', '<=', $d))
            ->when($filtros['cliente_id'] ?? null, fn ($w, $id) => $w->whereHas('bl', fn ($b) => $b->where('cliente_id', (int) $id)))
            ->when($texto('bl', 30), fn ($w, $t) => $w->whereHas('bl', fn ($b) => $b->where('numero_bl', 'ilike', "%{$t}%")))
            ->when($texto('numero', 60), fn ($w, $t) => $w->where('legalizaciones.numero_autorizacion', 'ilike', "%{$t}%"));
    }

    private function fecha(mixed $valor): ?string
    {
        return is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ? $valor : null;
    }
}

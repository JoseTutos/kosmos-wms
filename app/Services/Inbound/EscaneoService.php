<?php

namespace App\Services\Inbound;

use App\Enums\CodigoError;
use App\Exceptions\ReglaNegocioException;
use App\Models\Bodega;
use App\Models\Bulto;
use App\Models\Pallet;
use App\Models\Serial;
use App\Models\Ubicacion;

/**
 * D14 / T7: el escáner lee cualquier etiqueta y la app no sabe qué es. Un solo punto identifica el código:
 *   PLT-DDMMAA-0000 → pallet · BUL-DDMMAA-0000 → bulto · {SKU}-0000 → serial
 *   {BODEGA}/{UBICACION} → ubicación · cualquier otro → bodega (Maestros no permite códigos que choquen, CodigoMaestro).
 */
final class EscaneoService
{
    /** @return array{tipo: string, codigo: string, datos: array<string, mixed>} */
    public function identificar(string $codigo): array
    {
        $codigo = trim($codigo);
        $mayus = mb_strtoupper($codigo);

        $resultado = match (true) {
            (bool) preg_match('/^PLT-\d{6}-\d{4}$/', $mayus)          => $this->pallet($mayus),
            (bool) preg_match('/^BUL-\d{6}-\d{4}$/', $mayus)          => $this->bulto($mayus),
            (bool) preg_match('/^[A-Z]{3,6}-\d{5}-\d{4}$/', $mayus)   => $this->serial($mayus),
            str_contains($codigo, '/')                                 => $this->ubicacion($codigo),
            default                                                    => $this->bodega($codigo),
        };

        return $resultado ?? throw new ReglaNegocioException(
            CodigoError::CODIGO_NO_ENCONTRADO, "El código {$codigo} no corresponde a ningún pallet, bulto, bodega, ubicación ni serial.", ['codigo' => $codigo],
        );
    }

    private function pallet(string $codigo): ?array
    {
        $p = Pallet::with(['bl:id,numero_bl,estado', 'bodega:id,codigo,nombre', 'ubicacion:id,codigo'])->withCount('bultos')->where('codigo', $codigo)->first();

        return $p ? ['tipo' => 'pallet', 'codigo' => $p->codigo, 'datos' => [
            'estado'    => $p->estado->value,
            'bl'        => ['id' => $p->bl->id, 'numero_bl' => $p->bl->numero_bl, 'estado' => $p->bl->estado->value],
            'bodega'    => $p->bodega?->codigo,
            'ubicacion' => $p->ubicacion?->codigo,
            'bultos'    => $p->bultos_count,
        ]] : null;
    }

    private function bulto(string $codigo): ?array
    {
        $b = Bulto::with(['bl:id,numero_bl,estado', 'pallet:id,codigo', 'detalles.producto:id,sku'])->where('codigo', $codigo)->first();

        return $b ? ['tipo' => 'bulto', 'codigo' => $b->codigo, 'datos' => [
            'estado'        => $b->estado->value,
            'bl'            => ['id' => $b->bl->id, 'numero_bl' => $b->bl->numero_bl, 'estado' => $b->bl->estado->value],
            'pallet'        => $b->pallet?->codigo,
            'peso_bruto_kg' => $b->peso_bruto_kg,
            'lineas'        => $b->detalles->map(fn ($d) => ['sku' => $d->producto->sku, 'cantidad' => $d->cantidad_recibida])->values(),
        ]] : null;
    }

    private function serial(string $codigo): ?array
    {
        $s = Serial::with('bultoDetalle.producto:id,sku', 'bultoDetalle.bulto:id,codigo')->where('codigo', $codigo)->first();

        return $s ? ['tipo' => 'serial', 'codigo' => $s->codigo, 'datos' => [
            'estado'       => $s->estado->value,
            'estado_legal' => $s->estado_legal->value,
            'sku'          => $s->bultoDetalle->producto->sku,
            'bulto'        => $s->bultoDetalle->bulto->codigo,
        ]] : null;
    }

    private function ubicacion(string $codigo): ?array
    {
        [$codigoBodega, $codigoUbicacion] = explode('/', $codigo, 2);

        $u = Ubicacion::with('bodega')
            ->where('codigo', $codigoUbicacion)
            ->whereHas('bodega', fn ($q) => $q->where('codigo', $codigoBodega))
            ->first();

        return $u ? ['tipo' => 'ubicacion', 'codigo' => "{$u->bodega->codigo}/{$u->codigo}", 'datos' => [
            'estado' => $u->estado->value,
            'bodega' => $this->resumenBodega($u->bodega),
            'codigo_ubicacion' => $u->codigo,
        ]] : null;
    }

    private function bodega(string $codigo): ?array
    {
        $b = Bodega::query()->where('codigo', $codigo)->first();

        return $b ? ['tipo' => 'bodega', 'codigo' => $b->codigo, 'datos' => $this->resumenBodega($b)] : null;
    }

    private function resumenBodega(Bodega $b): array
    {
        return [
            'codigo'               => $b->codigo,
            'nombre'               => $b->nombre,
            'estado'               => $b->estado->value,
            'maneja_ubicaciones'   => $b->maneja_ubicaciones,
            'porcentaje_ocupacion' => $b->porcentaje_ocupacion,
        ];
    }
}

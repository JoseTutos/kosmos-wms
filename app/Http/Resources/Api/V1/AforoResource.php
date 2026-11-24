<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AforoDetalle;
use App\Services\Inbound\AforoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Aforo */
class AforoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'codigo'          => "AFO-{$this->id}",
            'estado'          => $this->estado->value,
            'bl'              => $this->whenLoaded('bl', fn () => [
                'id'        => $this->bl->id,
                'numero_bl' => $this->bl->numero_bl,
                'cliente'   => $this->bl->relationLoaded('cliente') ? $this->bl->cliente?->codigo_corto : null,
            ]),
            'lineas_total'    => $this->whenCounted('detalles'),
            'lineas_contadas' => $this->whenCounted('contadas'),
            'creado_en'       => $this->created_at?->toIso8601String(),
            'detalles'        => $this->whenLoaded('detalles', fn () => $this->detalles->map(fn (AforoDetalle $d) => self::detalle($d))->values()),
        ];
    }

    /** Una línea para la app: lo que hay que contar y su estado. */
    public static function detalle(AforoDetalle $d): array
    {
        $linea = $d->bultoDetalle;
        $producto = $linea->producto;

        return [
            'id'                  => $d->id,
            'bulto'               => $linea->bulto?->codigo,
            'pallet'              => $linea->bulto?->pallet?->codigo,
            'bodega'              => $linea->bulto?->pallet?->bodega?->codigo,
            'sku'                 => $producto->sku,
            'descripcion'         => $producto->descripcion,
            'unidad_medida'       => $producto->unidad_medida instanceof \BackedEnum ? $producto->unidad_medida->value : $producto->unidad_medida,
            'serializable'        => AforoService::serializable($producto),
            'cantidad_manifiesto' => $d->cantidad_manifiesto,
            'cantidad_contada'    => $d->cantidad_contada,
            'diferencia'          => $d->diferencia,
            'seriales'            => (int) ($linea->seriales_count ?? $linea->seriales()->count()),
        ];
    }
}

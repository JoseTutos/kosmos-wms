<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Bulto */
class BultoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'codigo'        => $this->codigo,
            'estado'        => $this->estado->value,
            'peso_bruto_kg' => $this->peso_bruto_kg,
            'pallet'        => $this->whenLoaded('pallet', fn () => $this->pallet?->codigo),
            'lineas'        => $this->whenLoaded('detalles', fn () => $this->detalles->map(fn ($d) => [
                'producto_id' => $d->producto_id,
                'sku'         => $d->producto?->sku,
                'descripcion' => $d->producto?->descripcion,
                'unidad'      => $d->producto?->unidad_medida?->value,
                'cantidad'    => $d->cantidad_recibida,
            ])->values()),
        ];
    }
}

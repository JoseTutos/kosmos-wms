<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Pallet */
class PalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'codigo'               => $this->codigo,
            'estado'               => $this->estado->value,
            'bl'                   => $this->whenLoaded('bl', fn () => ['id' => $this->bl->id, 'numero_bl' => $this->bl->numero_bl]),
            'bodega'               => $this->whenLoaded('bodega', fn () => $this->bodega?->codigo),
            'ubicacion'            => $this->whenLoaded('ubicacion', fn () => $this->ubicacion?->codigo),
            'referencia_ubicacion' => $this->referencia_ubicacion,
            'bultos'               => $this->whenLoaded('bultos', fn () => $this->bultos->pluck('codigo')->values()),
            'total_bultos'         => $this->whenLoaded('bultos', fn () => $this->bultos->count()),
        ];
    }
}

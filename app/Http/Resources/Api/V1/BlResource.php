<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Fila del listado de BL disponibles para recibir. @mixin \App\Models\Bl */
class BlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'numero_bl'         => $this->numero_bl,
            'estado'            => $this->estado->value,
            'fecha_ingreso'     => $this->fecha_ingreso?->toDateString(),
            'factura_comercial' => $this->factura_comercial,
            'cliente'           => $this->whenLoaded('cliente', fn () => [
                'codigo' => $this->cliente->codigo_corto, 'razon_social' => $this->cliente->razon_social,
            ]),
            'bultos_esperados'  => (int) ($this->detalles_sum_bultos_esperados ?? 0),
        ];
    }
}

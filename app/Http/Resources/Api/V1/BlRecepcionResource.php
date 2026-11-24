<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Estado de la recepción (maqueta 3.2.1): recibido vs. esperado en bultos, unidades y peso, pallets por estado,
 * productos del manifiesto (selector de la app) y si se puede finalizar. Todo calculado en el servidor.
 * Recibe el arreglo de RecepcionService::resumen().
 */
class BlRecepcionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $r = $this->resource;

        return [
            'bl'                   => $r['bl'],
            'bultos'               => $r['bultos'],
            'unidades'             => $r['unidades'],
            'peso_kg'              => $r['peso_kg'],
            'pallets'              => $r['pallets'],
            'productos'            => $r['productos'],
            'requiere_observacion' => $r['requiere_observacion'],
            'puede_finalizar'      => $r['puede_finalizar'],
        ];
    }
}

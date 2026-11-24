<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Bodega;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Respuesta de CUS-008. El sobrecupo solo se advierte (U6): la app muestra el aviso, no bloquea. */
class UbicacionResultadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'operacion'        => $this->resource['operacion'],
            'pallet'           => PalletResource::make($this->resource['pallet'])->resolve($request),
            'bodega'           => self::ocupacion($this->resource['bodega']),
            'bodega_origen'    => $this->resource['bodega_origen'] ? self::ocupacion($this->resource['bodega_origen']) : null,
            'alerta_sobrecupo' => $this->resource['alerta_sobrecupo'],
            'mensaje'          => $this->resource['alerta_sobrecupo']
                ? "Pallet ubicado. Atención: la bodega {$this->resource['bodega']->codigo} supera su capacidad ({$this->resource['bodega']->porcentaje_ocupacion} %)."
                : 'Pallet ubicado.',
        ];
    }

    public static function ocupacion(Bodega $b): array
    {
        return [
            'codigo'               => $b->codigo,
            'nombre'               => $b->nombre,
            'tamano_m2'            => $b->tamano_m2,
            'm2_ocupados'          => $b->m2_ocupados,
            'porcentaje_ocupacion' => $b->porcentaje_ocupacion,
        ];
    }
}

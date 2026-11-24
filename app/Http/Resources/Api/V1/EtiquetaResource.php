<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** { tipo, codigo, formato: "ZPL", contenido } — la app lo envía tal cual a la impresora (TCP 9100). */
class EtiquetaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tipo'      => $this->resource['tipo'],
            'codigo'    => $this->resource['codigo'],
            'formato'   => 'ZPL',
            'contenido' => $this->resource['zpl'],
        ];
    }
}

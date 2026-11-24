<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReimprimirEtiquetaRequest;
use App\Http\Resources\Api\V1\EtiquetaResource;
use App\Services\Inbound\EtiquetaService;

/** CUS-007 reimpresión desde la app (bulto o pallet) con motivo y bitácora. */
class EtiquetaController extends Controller
{
    public function __construct(private readonly EtiquetaService $etiquetas) {}

    /** POST /etiquetas/reimprimir → { data: { tipo, codigo, formato: "ZPL", contenido } } */
    public function reimprimir(ReimprimirEtiquetaRequest $request): EtiquetaResource
    {
        $modelo = $this->etiquetas->reimprimir(
            $request->validated('tipo'),
            $request->validated('codigo'),
            $request->motivo(),
            $request->validated('detalle'),
            $request->user(),
            'api',
        );

        return EtiquetaResource::make([
            'tipo'   => $request->validated('tipo'),
            'codigo' => $request->validated('codigo'),
            'zpl'    => $this->etiquetas->zpl($modelo),
        ]);
    }
}

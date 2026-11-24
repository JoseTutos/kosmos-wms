<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBultoRequest;
use App\Http\Resources\Api\V1\BlRecepcionResource;
use App\Http\Resources\Api\V1\BultoResource;
use App\Http\Resources\Api\V1\EtiquetaResource;
use App\Models\Bl;
use App\Models\Bulto;
use App\Services\Inbound\EtiquetaService;
use App\Services\Inbound\RecepcionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** CUS-006 bultos + CUS-007 (cada alta devuelve su etiqueta ZPL). */
class BultoController extends Controller
{
    public function __construct(
        private readonly RecepcionService $recepcion,
        private readonly EtiquetaService $etiquetas,
    ) {}

    /** POST /recepciones/{bl}/bultos → 201 { data: { bultos, etiquetas, recepcion } } */
    public function store(StoreBultoRequest $request, Bl $bl): JsonResponse
    {
        $bultos = $this->recepcion->registrarBultos($bl, $request->validated(), $request->user());

        return response()->json(['data' => [
            'bultos'    => BultoResource::collection($bultos)->resolve($request),
            'etiquetas' => EtiquetaResource::collection($bultos->map(fn (Bulto $b) => [
                'tipo' => 'bulto', 'codigo' => $b->codigo, 'zpl' => $this->etiquetas->zpl($b),
            ]))->resolve($request),
            'recepcion' => BlRecepcionResource::make($this->recepcion->resumen($bl->refresh()))->resolve($request),
        ]], 201);
    }

    /** GET /bultos/{codigo} */
    public function show(Bulto $bulto): BultoResource
    {
        return BultoResource::make($bulto->load(['detalles.producto', 'pallet']));
    }

    /** POST /bultos/{codigo}/anular */
    public function anular(Request $request, Bulto $bulto): JsonResponse
    {
        $bulto = $this->recepcion->anularBulto($bulto, $request->user());

        return response()->json(['data' => [
            'bulto'     => BultoResource::make($bulto)->resolve($request),
            'recepcion' => BlRecepcionResource::make($this->recepcion->resumen($bulto->bl))->resolve($request),
        ]]);
    }
}

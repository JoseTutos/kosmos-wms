<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EscaneoBultoRequest;
use App\Http\Resources\Api\V1\EtiquetaResource;
use App\Http\Resources\Api\V1\PalletResource;
use App\Models\Bl;
use App\Models\Pallet;
use App\Services\Inbound\EtiquetaService;
use App\Services\Inbound\PalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** CUS-006 armar pallets por escaneo + CUS-007 etiqueta del pallet. Las rutas usan el código PLT. */
class PalletController extends Controller
{
    public function __construct(
        private readonly PalletService $pallets,
        private readonly EtiquetaService $etiquetas,
    ) {}

    /** POST /recepciones/{bl}/pallets → 201 { data: { pallet, etiqueta } } */
    public function store(Request $request, Bl $bl): JsonResponse
    {
        $pallet = $this->pallets->crear($bl, $request->user());

        return response()->json(['data' => [
            'pallet'   => $this->recurso($pallet, $request),
            'etiqueta' => EtiquetaResource::make(['tipo' => 'pallet', 'codigo' => $pallet->codigo, 'zpl' => $this->etiquetas->zpl($pallet)])->resolve($request),
        ]], 201);
    }

    public function show(Pallet $pallet): PalletResource
    {
        return PalletResource::make($pallet->load(['bl', 'bodega', 'ubicacion', 'bultos']));
    }

    public function agregar(EscaneoBultoRequest $request, Pallet $pallet): JsonResponse
    {
        return $this->responder($this->pallets->agregar($pallet, $request->validated('bulto'), $request->user()), $request);
    }

    public function retirar(EscaneoBultoRequest $request, Pallet $pallet): JsonResponse
    {
        return $this->responder($this->pallets->retirar($pallet, $request->validated('bulto'), $request->user()), $request);
    }

    public function cerrar(Request $request, Pallet $pallet): JsonResponse
    {
        return $this->responder($this->pallets->cerrar($pallet, $request->user()), $request);
    }

    public function reabrir(Request $request, Pallet $pallet): JsonResponse
    {
        return $this->responder($this->pallets->reabrir($pallet, $request->user()), $request);
    }

    public function destroy(Request $request, Pallet $pallet): Response
    {
        $this->pallets->eliminar($pallet, $request->user());

        return response()->noContent();
    }

    private function responder(Pallet $pallet, Request $request): JsonResponse
    {
        return response()->json(['data' => $this->recurso($pallet, $request)]);
    }

    private function recurso(Pallet $pallet, Request $request): array
    {
        return PalletResource::make($pallet->load(['bl', 'bodega', 'ubicacion', 'bultos']))->resolve($request);
    }
}

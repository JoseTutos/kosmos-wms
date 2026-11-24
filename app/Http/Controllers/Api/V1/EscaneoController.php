<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Inbound\EscaneoService;
use Illuminate\Http\JsonResponse;

/** D14: GET /escaneos/{codigo} → { data: { tipo, codigo, datos } }. La ruta acepta "/" (BODEGA/UBICACION). */
class EscaneoController extends Controller
{
    public function __construct(private readonly EscaneoService $escaneos) {}

    public function show(string $codigo): JsonResponse
    {
        return response()->json(['data' => $this->escaneos->identificar(urldecode($codigo))]);
    }
}

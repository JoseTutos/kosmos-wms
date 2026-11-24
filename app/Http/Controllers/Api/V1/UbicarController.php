<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoActiva;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UbicarRequest;
use App\Http\Resources\Api\V1\UbicacionResultadoResource;
use App\Models\Bodega;
use App\Services\Inbound\UbicarMercanciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** CUS-008 Ubicar y trasladar desde la app del Operario. */
class UbicarController extends Controller
{
    public function __construct(private readonly UbicarMercanciaService $servicio) {}

    /** POST /almacenamiento/ubicar  { pallet, bodega, ubicacion?, escaneo, referencia? } → 200 */
    public function ubicar(UbicarRequest $request): UbicacionResultadoResource
    {
        return UbicacionResultadoResource::make($this->servicio->ubicar($request->validated(), $request->user()));
    }

    /** GET /almacenamiento/destinos → bodegas ACTIVAS con sus ubicaciones ACTIVAS y su ocupación, para elegir el destino. */
    public function destinos(Request $request): JsonResponse
    {
        $bodegas = Bodega::query()
            ->where('estado', EstadoActiva::ACTIVA->value)
            ->with(['ubicaciones' => fn ($q) => $q->where('estado', EstadoActiva::ACTIVA->value)->orderBy('codigo')])
            ->orderBy('codigo')
            ->get();

        return response()->json(['data' => $bodegas->map(fn (Bodega $b) => UbicacionResultadoResource::ocupacion($b) + [
            'maneja_ubicaciones' => $b->maneja_ubicaciones,
            'ubicaciones'        => $b->maneja_ubicaciones ? $b->ubicaciones->pluck('codigo')->values() : [],
        ])->values()]);
    }
}

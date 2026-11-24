<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoBl;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FinalizarRecepcionRequest;
use App\Http\Resources\Api\V1\BlRecepcionResource;
use App\Http\Resources\Api\V1\BlResource;
use App\Models\Bl;
use App\Services\Inbound\RecepcionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** CUS-006 en la app: BL disponibles, estado de la recepción, iniciar y finalizar. */
class RecepcionController extends Controller
{
    public function __construct(private readonly RecepcionService $recepcion) {}

    /** GET /recepciones?q= — BL PROGRAMADOS o EN_RECEPCION. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $texto = $request->string('q')->trim()->upper()->substr(0, 30)->value();

        $bls = Bl::query()
            ->with('cliente:id,codigo_corto,razon_social')
            ->withSum('detalles', 'bultos_esperados')
            ->whereIn('estado', [EstadoBl::PROGRAMADO->value, EstadoBl::EN_RECEPCION->value])
            ->when($texto, fn ($q) => $q->where('numero_bl', 'like', "%{$texto}%"))
            ->orderByRaw("CASE WHEN estado = 'EN_RECEPCION' THEN 0 ELSE 1 END")
            ->orderBy('fecha_ingreso')
            ->paginate(30);

        return BlResource::collection($bls);
    }

    /** GET /recepciones/{bl} — totales recibido vs. esperado y productos del manifiesto. */
    public function show(Bl $bl): BlRecepcionResource
    {
        return BlRecepcionResource::make($this->recepcion->resumen($bl));
    }

    public function iniciar(Request $request, Bl $bl): BlRecepcionResource
    {
        $bl = $this->recepcion->iniciar($bl, $request->user());

        return BlRecepcionResource::make($this->recepcion->resumen($bl));
    }

    public function finalizar(FinalizarRecepcionRequest $request, Bl $bl): BlRecepcionResource
    {
        $bl = $this->recepcion->finalizar($bl, $request->validated('observacion'), $request->user());

        return BlRecepcionResource::make($this->recepcion->resumen($bl));
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoAforo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegistrarConteoRequest;
use App\Http\Requests\Api\V1\RegistrarSerialesRequest;
use App\Http\Resources\Api\V1\AforoResource;
use App\Models\Aforo;
use App\Models\Serial;
use App\Services\Inbound\AforoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** CUS-009 app (Operario): aforos en proceso, conteo por línea y seriales opcionales. */
class AforoController extends Controller
{
    public function __construct(private readonly AforoService $aforos) {}

    /** GET /aforos → los EN_PROCESO, con su avance. */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Aforo::class);

        $aforos = Aforo::query()
            ->with('bl:id,numero_bl,cliente_id', 'bl.cliente:id,codigo_corto')
            ->withCount(['detalles', 'detalles as contadas_count' => fn ($q) => $q->whereNotNull('cantidad_contada')])
            ->where('estado', EstadoAforo::EN_PROCESO->value)
            ->orderBy('id')
            ->paginate(30);

        return AforoResource::collection($aforos);
    }

    /** GET /aforos/{aforo} → líneas a contar. */
    public function show(Aforo $aforo): AforoResource
    {
        Gate::authorize('view', $aforo);

        return AforoResource::make($this->cargar($aforo));
    }

    /** POST /aforos/{aforo}/conteos { detalle_id, cantidad } → 200 { data: { detalle, avance } } */
    public function conteos(RegistrarConteoRequest $request, Aforo $aforo): JsonResponse
    {
        Gate::authorize('contar', $aforo);

        $detalle = $this->aforos->registrarConteo($aforo, (int) $request->validated('detalle_id'), (string) $request->validated('cantidad'), $request->user());
        $detalle->load(['bultoDetalle' => fn ($q) => $q->withCount('seriales'), 'bultoDetalle.producto', 'bultoDetalle.bulto.pallet.bodega']);

        return response()->json(['data' => [
            'detalle' => AforoResource::detalle($detalle),
            'avance'  => $this->avance($aforo),
        ]]);
    }

    /** POST /aforos/{aforo}/seriales { detalle_id } → 201 { data: { detalle_id, seriales: [{ codigo, zpl }] } } */
    public function seriales(RegistrarSerialesRequest $request, Aforo $aforo): JsonResponse
    {
        Gate::authorize('contar', $aforo);

        $resultado = $this->aforos->generarSeriales($aforo, (int) $request->validated('detalle_id'), $request->user());
        $linea = $resultado['detalle']->bultoDetalle;

        return response()->json(['data' => [
            'detalle_id' => $resultado['detalle']->id,
            'sku'        => $linea->producto->sku,
            'total'      => $resultado['seriales']->count(),
            'seriales'   => $resultado['seriales']->map(fn (Serial $s) => [
                'codigo' => $s->codigo,
                'zpl'    => $this->aforos->zplSerial($s, $linea->producto->sku, $linea->bulto->codigo),
            ])->values(),
        ]], 201);
    }

    private function cargar(Aforo $aforo): Aforo
    {
        return $aforo->load([
            'bl:id,numero_bl,cliente_id', 'bl.cliente:id,codigo_corto',
            'detalles' => fn ($q) => $q->orderBy('id'),
            'detalles.bultoDetalle' => fn ($q) => $q->withCount('seriales'),
            'detalles.bultoDetalle.producto:id,sku,descripcion,unidad_medida',
            'detalles.bultoDetalle.bulto:id,codigo,pallet_id',
            'detalles.bultoDetalle.bulto.pallet:id,codigo,bodega_id',
            'detalles.bultoDetalle.bulto.pallet.bodega:id,codigo',
        ])->loadCount(['detalles', 'detalles as contadas_count' => fn ($q) => $q->whereNotNull('cantidad_contada')]);
    }

    /** @return array{lineas_total: int, lineas_contadas: int} */
    private function avance(Aforo $aforo): array
    {
        return [
            'lineas_total'    => $aforo->detalles()->count(),
            'lineas_contadas' => $aforo->detalles()->whereNotNull('cantidad_contada')->count(),
        ];
    }
}

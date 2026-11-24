<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoAforo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ActivarAforoRequest;
use App\Models\Aforo;
use App\Models\Bl;
use App\Services\Inbound\AforoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CUS-009 web (Supervisor): activar, seguir el conteo, cerrar o cancelar y descargar el reporte. */
class AforoController extends Controller
{
    public function __construct(private readonly AforoService $aforos) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Aforo::class);

        $texto = $request->string('q')->trim()->upper()->substr(0, 30)->value();

        $aforos = Aforo::query()
            ->with('bl:id,numero_bl,cliente_id', 'bl.cliente:id,codigo_corto,razon_social')
            ->withCount(['detalles', 'detalles as contadas_count' => fn ($q) => $q->whereNotNull('cantidad_contada')])
            ->when($texto, fn ($q) => $q->whereHas('bl', fn ($b) => $b->where('numero_bl', 'like', "%{$texto}%")))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->orderByRaw("CASE WHEN estado = 'EN_PROCESO' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('aforos.index', ['aforos' => $aforos, 'estados' => EstadoAforo::cases()]);
    }

    /** Paso 1: elegir el BL (?bl=ID). Paso 2: elegir los bultos UBICADOS (todos marcados). */
    public function create(Request $request): View
    {
        Gate::authorize('create', Aforo::class);

        $bls = Bl::query()
            ->with('cliente:id,codigo_corto')
            ->whereIn('estado', array_map(fn ($e) => $e->value, AforoService::BL_AFORABLE))
            ->whereDoesntHave('aforos', fn ($q) => $q->where('estado', EstadoAforo::EN_PROCESO->value))
            ->orderByDesc('fecha_ingreso')
            ->limit(300)
            ->get(['id', 'numero_bl', 'cliente_id', 'estado', 'fecha_ingreso']);

        $bl = $request->filled('bl') ? $bls->firstWhere('id', (int) $request->bl) : null;

        return view('aforos.create', [
            'bls'    => $bls,
            'bl'     => $bl,
            'bultos' => $bl ? $this->aforos->bultosAforables($bl) : collect(),
        ]);
    }

    public function store(ActivarAforoRequest $request): RedirectResponse
    {
        Gate::authorize('create', Aforo::class);

        $aforo = $this->aforos->activar(Bl::findOrFail($request->validated('bl_id')), $request->validated('bultos'), $request->user());

        return redirect()->route('aforos.show', $aforo)->with('ok', "Aforo AFO-{$aforo->id} activado. Los operarios ya pueden contar desde la app.");
    }

    public function show(Aforo $aforo): View
    {
        Gate::authorize('view', $aforo);

        $aforo->load([
            'bl:id,numero_bl,cliente_id,estado', 'bl.cliente:id,codigo_corto,razon_social',
            'detalles' => fn ($q) => $q->orderBy('id'),
            'detalles.bultoDetalle' => fn ($q) => $q->withCount('seriales'),
            'detalles.bultoDetalle.producto:id,sku,descripcion,unidad_medida',
            'detalles.bultoDetalle.bulto:id,codigo,pallet_id', 'detalles.bultoDetalle.bulto.pallet:id,codigo,bodega_id',
            'detalles.bultoDetalle.bulto.pallet.bodega:id,codigo',
        ]);

        return view('aforos.show', ['aforo' => $aforo]);
    }

    public function cerrar(Request $request, Aforo $aforo): RedirectResponse
    {
        Gate::authorize('cerrar', $aforo);

        $this->aforos->cerrar($aforo, $request->user());

        return redirect()->route('aforos.show', $aforo)->with('ok', 'Aforo cerrado: las cantidades quedaron corregidas y el reporte de diferencias está listo.');
    }

    public function cancelar(Request $request, Aforo $aforo): RedirectResponse
    {
        Gate::authorize('cancelar', $aforo);

        $this->aforos->cancelar($aforo, $request->user());

        return redirect()->route('aforos.show', $aforo)->with('ok', 'Aforo cancelado: los conteos no se aplicaron y los bultos volvieron a UBICADO.');
    }

    /** GET /aforos/{aforo}/reporte/{formato}  formato = pdf | xlsx */
    public function reporte(Aforo $aforo, string $formato): StreamedResponse
    {
        Gate::authorize('reporte', $aforo);

        $ruta = $formato === 'xlsx' ? AforoService::rutaExcel($aforo->reporte_path) : $aforo->reporte_path;

        abort_unless(Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->download($ruta, "diferencias-aforo-{$aforo->id}.{$formato}");
    }
}

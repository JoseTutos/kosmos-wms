<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoActivo;
use App\Enums\EstadoBl;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PreinspeccionBlRequest;
use App\Http\Requests\Web\StoreBlRequest;
use App\Http\Requests\Web\UpdateBlRequest;
use App\Models\Bl;
use App\Models\Cliente;
use App\Models\Producto;
use App\Services\Inbound\BlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** CUS-005 Registrar BL (Supervisor). Controlador delgado: valida, autoriza y delega en BlService. */
class BlController extends Controller
{
    public function __construct(private readonly BlService $bls) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Bl::class);

        $texto = $request->string('q')->trim()->substr(0, 50)->value();

        $bls = Bl::query()
            ->with('cliente:id,codigo_corto,razon_social')
            ->withCount('detalles')
            ->withSum('detalles', 'bultos_esperados')
            ->withExists(['bultos', 'pallets'])   // BlPolicy::delete sin consultas por fila
            ->when($texto, fn ($q) => $q->where(fn ($w) => $w
                ->where('numero_bl', 'ilike', "%{$texto}%")
                ->orWhere('factura_comercial', 'ilike', "%{$texto}%")))
            ->when($request->cliente_id, fn ($q, $id) => $q->where('cliente_id', $id))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('bls.index', [
            'bls'      => $bls,
            'clientes' => Cliente::orderBy('razon_social')->get(['id', 'codigo_corto', 'razon_social']),
            'estados'  => EstadoBl::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Bl::class);

        return view('bls.create', [
            'clientes'  => Cliente::where('estado', EstadoActivo::ACTIVO)->orderBy('razon_social')->get(['id', 'codigo_corto', 'razon_social']),
            'productos' => $this->opcionesProductos(Producto::where('estado', EstadoActivo::ACTIVO)),
        ]);
    }

    public function store(StoreBlRequest $request): RedirectResponse
    {
        $bl = $this->bls->crear($request->validated(), $request->file('acta'));

        return redirect()->route('bls.show', $bl)->with('ok', "BL {$bl->numero_bl} registrado.");
    }

    public function show(Bl $bl): View
    {
        Gate::authorize('view', $bl);

        $bl->load(['cliente:id,codigo_corto,razon_social,nit', 'detalles.producto:id,sku,descripcion,unidad_medida'])
            ->loadCount(['bultos', 'pallets']);

        return view('bls.show', ['bl' => $bl]);
    }

    public function edit(Bl $bl): View
    {
        Gate::authorize('update', $bl);

        $bl->load(['cliente:id,codigo_corto,razon_social', 'detalles']);

        // Activos del cliente + los que ya están en el BL (si alguno se inactivó, se ve y la validación lo señala)
        $productos = Producto::where('cliente_id', $bl->cliente_id)
            ->where(fn ($q) => $q->where('estado', EstadoActivo::ACTIVO)->orWhereIn('id', $bl->detalles->pluck('producto_id')));

        return view('bls.edit', ['bl' => $bl, 'productos' => $this->opcionesProductos($productos)]);
    }

    public function update(UpdateBlRequest $request, Bl $bl): RedirectResponse
    {
        $bl = $this->bls->actualizar($bl, $request->validated(), $request->file('acta'), $request->boolean('quitar_acta'));

        return redirect()->route('bls.show', $bl)->with('ok', "BL {$bl->numero_bl} actualizado.");
    }

    public function destroy(Bl $bl): RedirectResponse
    {
        Gate::authorize('delete', $bl);

        $numero = $bl->numero_bl;
        $this->bls->eliminar($bl);

        return redirect()->route('bls.index')->with('ok', "BL {$numero} eliminado.");
    }

    public function preinspeccion(PreinspeccionBlRequest $request, Bl $bl): RedirectResponse
    {
        $this->bls->registrarPreinspeccion($bl, $request->validated('fecha_preinspeccion'), $request->file('acta'), $request->boolean('quitar_acta'));

        return back()->with('ok', 'Preinspección guardada.');
    }

    /** Descarga privada del acta (storage/app/private, nunca pública). */
    public function acta(Bl $bl): BinaryFileResponse
    {
        Gate::authorize('view', $bl);

        $ruta = $this->bls->rutaActa($bl);
        abort_unless($ruta, 404, 'El BL no tiene acta de preinspección.');

        $extension = pathinfo($ruta, PATHINFO_EXTENSION);

        $nombre = str_replace(['/', '\\'], '-', $bl->numero_bl); // el número de BL admite "/"

        return response()->download($ruta, "acta-preinspeccion-{$nombre}.{$extension}");
    }

    /** @return Collection<int, array{id: int, cliente_id: int, texto: string, unidad: string}> */
    private function opcionesProductos($consulta): Collection
    {
        return $consulta->orderBy('sku')
            ->get(['id', 'cliente_id', 'sku', 'descripcion', 'unidad_medida'])
            ->map(fn (Producto $p) => [
                'id'         => $p->id,
                'cliente_id' => (int) $p->cliente_id,
                'texto'      => "{$p->sku} · {$p->descripcion}",
                'unidad'     => $p->unidad_medida->value,
            ]);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Enums\TipoDocumentoAutorizacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreLegalizacionRequest;
use App\Models\Bl;
use App\Models\Cliente;
use App\Models\Legalizacion;
use App\Services\Control\LegalizacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CUS-010 Registrar Legalización (Analista y Supervisor). Controlador delgado: valida, autoriza y delega. */
class LegalizacionController extends Controller
{
    public function __construct(private readonly LegalizacionService $legalizaciones) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Legalizacion::class);

        $texto = $request->string('q')->trim()->substr(0, 60)->value();

        $legalizaciones = Legalizacion::query()
            ->with(['bl:id,numero_bl,cliente_id', 'bl.cliente:id,codigo_corto,razon_social', 'usuario:id,nombre'])
            ->withCount('detalles')
            ->when($texto, fn ($q) => $q->where(fn ($w) => $w
                ->where('consecutivo', 'ilike', "%{$texto}%")
                ->orWhere('numero_autorizacion', 'ilike', "%{$texto}%")
                ->orWhereHas('bl', fn ($b) => $b->where('numero_bl', 'ilike', "%{$texto}%"))))
            ->when($request->cliente_id, fn ($q, $id) => $q->whereHas('bl', fn ($b) => $b->where('cliente_id', $id)))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('legalizaciones.index', [
            'legalizaciones' => $legalizaciones,
            'clientes'       => Cliente::orderBy('razon_social')->get(['id', 'codigo_corto', 'razon_social']),
        ]);
    }

    /** Asistente de 4 pasos. El paso 1 elige el BL por GET (?bl=); los pasos 2 a 4 son Alpine en la misma página. */
    public function create(Request $request): View
    {
        Gate::authorize('create', Legalizacion::class);

        $bls = $this->legalizaciones->blsLegalizables();
        $bl = $request->filled('bl') ? $bls->firstWhere('id', $request->integer('bl')) : null;

        return view('legalizaciones.create', [
            'bls'          => $bls,
            'bl'           => $bl,
            'blNoValido'   => $request->filled('bl') && ! $bl,
            'datos'        => $bl ? $this->legalizaciones->cargarBl($bl) : null,
            'tipos'        => TipoDocumentoAutorizacion::opciones(),
        ]);
    }

    public function store(StoreLegalizacionRequest $request): RedirectResponse
    {
        $bl = Bl::findOrFail($request->integer('bl_id'));

        $legalizacion = $this->legalizaciones->registrar($bl, $request->items(), $request->autorizacion(), $request->file('soporte'), $request->user());

        $redireccion = redirect()->route('legalizaciones.show', $legalizacion)
            ->with('ok', "Legalización {$legalizacion->consecutivo} registrada.")
            ->with('limpiar_carrito', $bl->id);   // L10: la vista borra el carrito del navegador

        $previas = $this->legalizaciones->usosDeAutorizacion($legalizacion->numero_autorizacion, $legalizacion->id);

        return $previas->isEmpty() ? $redireccion : $redireccion->with('aviso',
            "El número de autorización {$legalizacion->numero_autorizacion} ya se había usado en ".$previas->pluck('consecutivo')->implode(', ').' (L12).'
        );
    }

    public function show(Legalizacion $legalizacion): View
    {
        Gate::authorize('view', $legalizacion);

        $this->legalizaciones->cargarDetalle($legalizacion);

        return view('legalizaciones.show', [
            'legalizacion' => $legalizacion,
            'otrosUsos'    => $this->legalizaciones->usosDeAutorizacion($legalizacion->numero_autorizacion, $legalizacion->id),
        ]);
    }

    /** Descarga privada del soporte (storage/app/private, nunca pública). */
    public function soporte(Legalizacion $legalizacion): StreamedResponse
    {
        Gate::authorize('view', $legalizacion);

        $disco = Storage::disk('local');
        abort_unless($disco->exists($legalizacion->soporte_path), 404, 'El soporte no está disponible.');

        $extension = pathinfo($legalizacion->soporte_path, PATHINFO_EXTENSION);

        return $disco->download($legalizacion->soporte_path, "soporte-{$legalizacion->consecutivo}.{$extension}");
    }

    /** L12: aviso no bloqueante mientras se escribe el número (paso 3 del asistente). */
    public function autorizacion(Request $request): JsonResponse
    {
        Gate::authorize('create', Legalizacion::class);

        $usos = $this->legalizaciones->usosDeAutorizacion($request->string('numero')->substr(0, 60)->value());

        return response()->json(['usos' => $usos->map(fn (Legalizacion $l) => [
            'consecutivo' => $l->consecutivo,
            'bl'          => $l->bl?->numero_bl,
            'fecha'       => $l->created_at?->format('d/m/Y'),
            'url'         => route('legalizaciones.show', $l),
        ])->values()]);
    }
}

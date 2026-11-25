<?php

namespace App\Http\Controllers\Web;

use App\Exports\LegalizacionesExport;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Legalizacion;
use App\Services\Control\HistoricoLegalizacionService;
use App\Services\Control\LegalizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CUS-012 Históricos y Auditoría de legalizaciones (Supervisor, Analista, Gerente). Solo lectura (RS-51). */
class HistoricoLegalizacionController extends Controller
{
    private const FILTROS = ['desde', 'hasta', 'cliente_id', 'bl', 'numero'];

    public function __construct(
        private readonly HistoricoLegalizacionService $historico,
        private readonly LegalizacionService $legalizaciones,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('verHistorico', Legalizacion::class);

        return view('historicos.legalizaciones.index', [
            'legalizaciones' => $this->historico->consulta($request->only(self::FILTROS))->paginate(20)->withQueryString(),
            'clientes'       => Cliente::orderBy('razon_social')->get(['id', 'codigo_corto', 'razon_social']),
        ]);
    }

    /** H2: los mismos filtros de la lista, en Excel o CSV. */
    public function exportar(Request $request, string $formato): StreamedResponse
    {
        Gate::authorize('verHistorico', Legalizacion::class);

        $filtros = $request->only(self::FILTROS);

        activity('legalizacion')->causedBy($request->user())
            ->withProperties(['formato' => $formato, 'filtros' => $filtros, 'ip' => $request->ip()])
            ->log('histórico exportado');

        $export = new LegalizacionesExport($this->historico->filasExporte($filtros));
        $nombre = 'legalizaciones-'.now(config('kosmos.zona_horaria', 'America/Bogota'))->format('Ymd-His');

        return $formato === 'xlsx' ? $export->xlsx($nombre) : $export->csv($nombre);
    }

    /** H1: detalle por bulto, producto y serial, con el soporte. Misma vista que CUS-010, en modo histórico. */
    public function show(Legalizacion $legalizacion): View
    {
        Gate::authorize('verHistorico', Legalizacion::class);

        return view('legalizaciones.show', [
            'legalizacion' => $this->legalizaciones->cargarDetalle($legalizacion),
            'otrosUsos'    => $this->legalizaciones->usosDeAutorizacion($legalizacion->numero_autorizacion, $legalizacion->id),
            'historico'    => true,
        ]);
    }

    public function soporte(Legalizacion $legalizacion): StreamedResponse
    {
        Gate::authorize('verHistorico', Legalizacion::class);

        $disco = Storage::disk('local');
        abort_unless($disco->exists($legalizacion->soporte_path), 404, 'El soporte no está disponible.');

        $extension = pathinfo($legalizacion->soporte_path, PATHINFO_EXTENSION);

        return $disco->download($legalizacion->soporte_path, "soporte-{$legalizacion->consecutivo}.{$extension}");
    }
}

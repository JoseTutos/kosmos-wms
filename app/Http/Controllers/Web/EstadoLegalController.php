<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Bl;
use App\Models\Cliente;
use App\Models\Legalizacion;
use App\Services\Control\EstadoLegalService;
use App\Services\Control\LegalizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** CUS-011 Estado Legal Activo (Supervisor, Analista, Gerente). Solo lectura. */
class EstadoLegalController extends Controller
{
    public function __construct(private readonly EstadoLegalService $estadoLegal) {}

    public function index(Request $request): View
    {
        Gate::authorize('verEstadoLegal', Legalizacion::class);

        $filtros = $request->only(['cliente_id', 'bl', 'dias_min', 'dias_max']);

        return view('estado-legal.index', [
            'bls'      => $this->estadoLegal->consulta($filtros)->paginate(20)->withQueryString(),
            'clientes' => Cliente::orderBy('razon_social')->get(['id', 'codigo_corto', 'razon_social']),
        ]);
    }

    public function show(Bl $bl): View
    {
        Gate::authorize('verEstadoLegal', Legalizacion::class);

        $bl->load('cliente:id,codigo_corto,razon_social');

        return view('estado-legal.show', [
            'bl'           => $bl,
            'totales'      => $this->estadoLegal->totales($bl),
            'lineas'       => $this->estadoLegal->detalle($bl),
            'legalizable'  => LegalizacionService::esLegalizable($bl),
        ]);
    }
}

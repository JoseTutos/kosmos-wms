<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoActiva;
use App\Enums\EstadoPallet;
use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bodega\StoreBodegaRequest;
use App\Http\Requests\Bodega\UpdateBodegaRequest;
use App\Models\Bodega;
use App\Support\Cantidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kosmos WMS · Capa de Presentación (Controlador Web) · CUS-003 Gestionar Bodegas
 *
 * Actor: ADMIN_MAESTROS (middleware 'role:ADMIN_MAESTROS'; RE-7).
 *
 * Por qué NO hay servicio: una sola tabla y solo web. La ocupación (m2_ocupados) NO se
 * edita aquí: la recalcula el núcleo de inventario al ubicar o trasladar (RS-15, RS-39)
 * con Bodega::recalcularOcupacion().
 *
 * Cumple:
 *   - CUS-003: alta, edición, consulta con sus ubicaciones, inactivación y reactivación.
 *     Sin borrado (el modelo lo impide).
 *   - Código de bodega único e inmutable (va impreso en la etiqueta, CUS-007).
 *   - Decisión de diseño: superar el 100 % de ocupación se permite y se marca en rojo.
 *   - No se inactiva una bodega con mercancía ubicada (m2_ocupados > 0): primero se traslada.
 *   - No se apagan las ubicaciones internas (RS-17) si hay pallets ubicados en ellas.
 *
 * Pendiente: la impresión de la etiqueta de bodega («include» CUS-007) se conecta cuando
 * exista EtiquetaService.
 */
class BodegaController extends Controller
{
    public function index(Request $request): View
    {
        $texto = $request->string('q')->trim()->substr(0, 100)->value();

        $bodegas = Bodega::query()
            ->when($texto, fn ($q) => $q->where(fn ($w) => $w
                ->where('codigo', 'ilike', "%{$texto}%")
                ->orWhere('nombre', 'ilike', "%{$texto}%")))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->withCount(['ubicaciones' => fn ($q) => $q->where('estado', EstadoActiva::ACTIVA->value)])
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return view('bodegas.index', ['bodegas' => $bodegas, 'estados' => EstadoActiva::cases()]);
    }

    public function create(): View
    {
        return view('bodegas.create');
    }

    public function store(StoreBodegaRequest $request): RedirectResponse
    {
        $bodega = Bodega::create($request->validated());

        return redirect()->route('bodegas.show', $bodega)->with('ok', "Bodega {$bodega->codigo} creada.");
    }

    public function show(Bodega $bodega): View
    {
        $bodega->load(['ubicaciones' => fn ($q) => $q
            ->withCount(['pallets as pallets_ubicados' => fn ($p) => $p->where('estado', EstadoPallet::UBICADO->value)])
            ->orderBy('codigo')]);

        return view('bodegas.show', ['bodega' => $bodega]);
    }

    public function edit(Bodega $bodega): View
    {
        return view('bodegas.edit', ['bodega' => $bodega]);
    }

    public function update(UpdateBodegaRequest $request, Bodega $bodega): RedirectResponse
    {
        $datos = $request->validated();

        // RS-17: apagar las ubicaciones internas dejaría pallets apuntando a ubicaciones "fantasma".
        if ($bodega->maneja_ubicaciones && ! $datos['maneja_ubicaciones']
            && $bodega->pallets()->whereNotNull('ubicacion_id')->where('estado', EstadoPallet::UBICADO->value)->exists()) {
            throw new ReglaNegocioException('No se pueden desactivar las ubicaciones internas: hay pallets ubicados en ellas. Trasládelos primero.');
        }

        $bodega->update($datos);

        return redirect()->route('bodegas.show', $bodega)->with('ok', 'Bodega actualizada.');
    }

    public function inactivar(Bodega $bodega): RedirectResponse
    {
        if (Cantidad::esPositiva($bodega->m2_ocupados)) {
            throw new ReglaNegocioException("La bodega {$bodega->codigo} tiene mercancía ubicada ({$bodega->m2_ocupados} m²). Trasládela antes de inactivarla.");
        }

        $bodega->inactivar(); // dominio: deja de aceptar mercancía (aceptaMercancia)
        $bodega->save();

        return back()->with('ok', "Bodega {$bodega->codigo} inactivada.");
    }

    public function activar(Bodega $bodega): RedirectResponse
    {
        $bodega->activar();
        $bodega->save();

        return back()->with('ok', "Bodega {$bodega->codigo} reactivada.");
    }
}

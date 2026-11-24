<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoActivo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\StoreClienteRequest;
use App\Http\Requests\Cliente\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kosmos WMS · Capa de Presentación (Controlador Web) · CUS-001 Gestionar Clientes Importadores
 *
 * Actor: ADMIN_MAESTROS (middleware 'role:ADMIN_MAESTROS' en la ruta; RE-7).
 *
 * Por qué NO hay servicio: toca una sola tabla, no genera movimientos ni consecutivos
 * y solo se usa desde la web. Las reglas de la entidad ya viven en el modelo Cliente.
 *
 * Cumple:
 *   - CUS-001: alta, edición, inactivación y reactivación. Sin borrado físico (RS-04;
 *     el modelo lo impide en 'deleting').
 *   - NIT (9 dígitos) y código corto únicos e INMUTABLES: el UpdateRequest no los acepta
 *     y el trait CamposInmutables del modelo los bloquea como segunda barrera.
 *   - Decisión de diseño: inactivar solo saca al cliente de la operación NUEVA
 *     (BL y productos). Lo que ya está en bodega se sigue legalizando y despachando.
 *
 * Rendimiento: listado paginado; productos y "¿tiene inventario?" se calculan en la
 * misma consulta (withCount / withExists), sin consultas por fila.
 */
class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $texto = $request->string('q')->trim()->substr(0, 100)->value();

        $clientes = Cliente::query()
            ->when($texto, fn ($q) => $q->where(fn ($w) => $w
                ->where('razon_social', 'ilike', "%{$texto}%")
                ->orWhere('nit', 'like', "%{$texto}%")
                ->orWhere('codigo_corto', 'ilike', "%{$texto}%")))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->withCount('productos')
            ->withExists(['existencias as tiene_inventario' => fn ($q) => $q
                ->where(fn ($w) => $w->where('cantidad_cuarentena', '>', 0)->orWhere('cantidad_legalizada', '>', 0))])
            ->orderBy('razon_social')
            ->paginate(20)
            ->withQueryString();

        return view('clientes.index', ['clientes' => $clientes, 'estados' => EstadoActivo::cases()]);
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function store(StoreClienteRequest $request): RedirectResponse
    {
        $cliente = Cliente::create($request->validated());

        return redirect()->route('clientes.index')
            ->with('ok', "Cliente {$cliente->razon_social} ({$cliente->codigo_corto}) creado.");
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', ['cliente' => $cliente]);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated()); // solo razón social, dirección, teléfono y contacto

        return redirect()->route('clientes.index')->with('ok', 'Cliente actualizado.');
    }

    /** RS-04: se inactiva, no se borra. Bloquea BL y productos nuevos; lo existente sigue su trámite. */
    public function inactivar(Cliente $cliente): RedirectResponse
    {
        $cliente->inactivar(); // dominio
        $cliente->save();

        $conInventario = $cliente->existencias()
            ->where(fn ($w) => $w->where('cantidad_cuarentena', '>', 0)->orWhere('cantidad_legalizada', '>', 0))
            ->exists();

        return back()->with('ok', $conInventario
            ? "Cliente {$cliente->codigo_corto} inactivado. Aún tiene mercancía en bodega: podrá seguir legalizándola y despachándola."
            : "Cliente {$cliente->codigo_corto} inactivado.");
    }

    public function activar(Cliente $cliente): RedirectResponse
    {
        $cliente->activar(); // dominio
        $cliente->save();

        return back()->with('ok', "Cliente {$cliente->codigo_corto} reactivado.");
    }
}

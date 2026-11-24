<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoPallet;
use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ubicacion\StoreUbicacionRequest;
use App\Models\Bodega;
use App\Models\Ubicacion;
use Illuminate\Http\RedirectResponse;

/**
 * Kosmos WMS · Capa de Presentación (Controlador Web) · CUS-003 Ubicaciones internas
 *
 * Actor: ADMIN_MAESTROS. Las ubicaciones se gestionan DENTRO de la ficha de su bodega.
 *
 * Cumple:
 *   - RS-17: ubicaciones opcionales; solo en bodegas con maneja_ubicaciones = true
 *     (el modelo Ubicacion lo valida al crear). Código único dentro de su bodega.
 *   - Sin edición del código: va impreso en la etiqueta (CUS-007). Si se creó mal,
 *     se inactiva y se crea otra. Sin borrado.
 *   - No se inactiva una ubicación con pallets ubicados.
 *
 * Por qué NO hay servicio: una sola tabla, sin movimientos ni consecutivos.
 */
class UbicacionController extends Controller
{
    public function store(StoreUbicacionRequest $request, Bodega $bodega): RedirectResponse
    {
        $ubicacion = $bodega->ubicaciones()->create($request->validated());

        return back()->with('ok', "Ubicación {$ubicacion->codigo} creada en {$bodega->codigo}.");
    }

    public function inactivar(Ubicacion $ubicacion): RedirectResponse
    {
        if ($ubicacion->pallets()->where('estado', EstadoPallet::UBICADO->value)->exists()) {
            throw new ReglaNegocioException("La ubicación {$ubicacion->codigo} tiene pallets ubicados. Trasládelos antes de inactivarla.");
        }

        $ubicacion->inactivar();
        $ubicacion->save();

        return back()->with('ok', "Ubicación {$ubicacion->codigo} inactivada.");
    }

    public function activar(Ubicacion $ubicacion): RedirectResponse
    {
        $ubicacion->activar();
        $ubicacion->save();

        return back()->with('ok', "Ubicación {$ubicacion->codigo} reactivada.");
    }
}

<?php

namespace App\Services\Nucleo;

use App\Models\Bodega;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula m2_ocupados de una bodega (RS-15, D9): pallets UBICADOS × configuraciones.area_pallet_m2.
 * Bloquea la bodega para que dos ubicaciones simultáneas no se pisen el cálculo.
 * Ocupación > 100 % es válida: la vista la muestra con alerta (decisión de Maestros).
 */
final class OcupacionService
{
    public function __construct(private readonly ConfiguracionService $configuracion) {}

    public function recalcular(Bodega|int $bodega): Bodega
    {
        $id = $bodega instanceof Bodega ? $bodega->getKey() : $bodega;

        return DB::transaction(function () use ($id) {
            $bodega = Bodega::query()->whereKey($id)->lockForUpdate()->firstOrFail();

            $bodega->recalcularOcupacion($this->configuracion->decimal('area_pallet_m2')); // dominio (Bodega)
            $bodega->save();

            return $bodega;
        });
    }
}

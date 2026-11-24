<?php

namespace App\Models\Concerns;

use App\Exceptions\ReglaNegocioException;
use Illuminate\Database\Eloquent\Model;

/** Impide modificar columnas que no pueden cambiar después de crearse (NIT, SKU, BL de un bulto...). */
trait CamposInmutables
{
    /** @return array<int, string> */
    abstract protected function camposInmutables(): array;

    public static function bootCamposInmutables(): void
    {
        static::updating(function (Model $modelo) {
            foreach ($modelo->camposInmutables() as $campo) {
                if ($modelo->isDirty($campo)) {
                    throw ReglaNegocioException::campoInmutable(class_basename($modelo), $campo);
                }
            }
        });
    }
}

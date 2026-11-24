<?php

namespace App\Models\Concerns;

use App\Enums\Contracts\MaquinaEstados;
use App\Exceptions\ReglaNegocioException;
use Illuminate\Database\Eloquent\Model;

/**
 * Aplica la máquina de estados de la columna `estado`.
 * - cambiarEstado() valida la transición en memoria.
 * - Al guardar, se revalida contra el valor original (protege también
 *   contra asignaciones directas tipo $modelo->estado = ...).
 */
trait TieneEstado
{
    public static function bootTieneEstado(): void
    {
        static::updating(function (Model $modelo) {
            if (! $modelo->isDirty('estado')) {
                return;
            }

            $anterior = $modelo->getOriginal('estado');
            $nuevo    = $modelo->estado;

            if ($anterior instanceof MaquinaEstados && $nuevo instanceof MaquinaEstados && ! $anterior->puedePasarA($nuevo)) {
                throw ReglaNegocioException::transicionInvalida(class_basename($modelo), $anterior, $nuevo);
            }
        });
    }

    protected function cambiarEstado(MaquinaEstados $nuevo): void
    {
        $actual = $this->estado;

        if ($actual instanceof MaquinaEstados && ! $actual->puedePasarA($nuevo)) {
            throw ReglaNegocioException::transicionInvalida(class_basename($this), $actual, $nuevo);
        }

        $this->estado = $nuevo;
    }

    public function estaEn(MaquinaEstados ...$estados): bool
    {
        return in_array($this->estado, $estados, true);
    }
}

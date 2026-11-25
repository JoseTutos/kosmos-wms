<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * Registros de solo inserción del Módulo 3 (RS-48, RS-51, L18): no se editan ni se borran;
 * un error se corrige con un Ajuste (CUS-019). El modelo además declara UPDATED_AT = null.
 */
trait SoloInsercion
{
    public static function bootSoloInsercion(): void
    {
        $rechazar = fn () => throw new LogicException(
            class_basename(static::class).' es de solo inserción: corrija con un ajuste (RS-48, RS-57).'
        );

        static::updating($rechazar);
        static::deleting($rechazar);
    }
}

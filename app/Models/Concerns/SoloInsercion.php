<?php

namespace App\Models\Concerns;

use App\Exceptions\ReglaNegocioException;
use Illuminate\Database\Eloquent\Model;

/**
 * Tablas append-only (movimientos, auditoria, legalizaciones,
 * legalizacion_detalles, ajustes). Bloquea UPDATE/DELETE desde Eloquent;
 * los triggers y el REVOKE de PostgreSQL son la última barrera (RS-56, SEG02).
 */
trait SoloInsercion
{
    public static function bootSoloInsercion(): void
    {
        static::updating(function (Model $modelo) {
            throw ReglaNegocioException::soloInsercion($modelo->getTable(), 'UPDATE');
        });

        static::deleting(function (Model $modelo) {
            throw ReglaNegocioException::soloInsercion($modelo->getTable(), 'DELETE');
        });
    }
}

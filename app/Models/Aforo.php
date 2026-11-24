<?php

namespace App\Models;

use App\Enums\CodigoError;
use App\Enums\EstadoAforo;
use App\Exceptions\ReglaNegocioException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Aforo / inventario detallado de un BL (CUS-009, D1, D2). EN_PROCESO → CERRADO | CANCELADO. */
class Aforo extends Model
{
    protected $table = 'aforos';

    protected $fillable = ['bl_id'];

    protected $attributes = [
        'estado' => 'EN_PROCESO',
    ];

    protected function casts(): array
    {
        return [
            'bl_id'  => 'integer',
            'estado' => EstadoAforo::class,
        ];
    }

    public function bl(): BelongsTo
    {
        return $this->belongsTo(Bl::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(AforoDetalle::class);
    }

    // ---------------- Dominio ----------------

    public function estaEnProceso(): bool
    {
        return $this->estado === EstadoAforo::EN_PROCESO;
    }

    public function exigirEnProceso(): void
    {
        if (! $this->estaEnProceso()) {
            throw new ReglaNegocioException(CodigoError::AFORO_NO_EN_PROCESO, datos: ['aforo' => $this->getKey(), 'estado' => $this->estado->value]);
        }
    }

    /** Todas las líneas deben estar contadas. Las diferencias las aplica AforoService (movimiento AFORO, D2). */
    public function cerrar(): void
    {
        $this->exigirEnProceso();

        $pendientes = $this->detalles()->whereNull('cantidad_contada')->count();

        if ($pendientes > 0) {
            throw new ReglaNegocioException(CodigoError::AFORO_INCOMPLETO, datos: ['aforo' => $this->getKey(), 'pendientes' => $pendientes]);
        }

        $this->estado = EstadoAforo::CERRADO;
    }

    public function cancelar(): void
    {
        $this->exigirEnProceso();
        $this->estado = EstadoAforo::CANCELADO;
    }
}

<?php

namespace App\Models;

use App\Enums\EstadoActiva;
use App\Exceptions\ReglaNegocioException;
use App\Models\Concerns\CamposInmutables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
//use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Ubicación interna «ubicaciones». Código único dentro de la bodega (RS-17).
 */
class Ubicacion extends Model
{
    use CamposInmutables/*, LogsActivity*/;

    protected $table = 'ubicaciones';

    protected $fillable = [
        'bodega_id',
        'codigo',
    ];

    protected $attributes = [
        'estado' => 'ACTIVA',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoActiva::class,
        ];
    }

    protected function camposInmutables(): array
    {
        return ['bodega_id', 'codigo']; // el código va impreso en la etiqueta (CUS-007)
    }

    protected static function booted(): void
    {
        static::creating(function (Ubicacion $ubicacion) {
            if (! $ubicacion->bodega?->maneja_ubicaciones) {
                throw new ReglaNegocioException('La bodega no maneja ubicaciones internas (RS-17).');
            }
        });
    }

    /** CUS-018: altas, cambios e inactivaciones de maestros van a la bitácora. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('maestros')
            ->logOnly(['bodega_id', 'codigo', 'estado'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ---------------- Relaciones ----------------

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    public function pallets(): HasMany
    {
        return $this->hasMany(Pallet::class);
    }

    // ---------------- Dominio ----------------

    public function estaActiva(): bool
    {
        return $this->estado === EstadoActiva::ACTIVA;
    }

    public function inactivar(): void
    {
        $this->estado = EstadoActiva::INACTIVA;
    }

    public function activar(): void
    {
        $this->estado = EstadoActiva::ACTIVA;
    }
}

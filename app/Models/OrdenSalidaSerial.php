<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Clase asociativa «orden_salida_seriales» — PK compuesta (orden_salida_detalle_id, serial_id).
 */
class OrdenSalidaSerial extends Pivot
{
    protected $table = 'orden_salida_seriales';

    public $incrementing = false;

    protected $fillable = [
        'orden_salida_detalle_id',
        'serial_id',
        'confirmado',
    ];

    protected $attributes = [
        'confirmado' => false,
    ];

    protected function casts(): array
    {
        return [
            'confirmado' => 'boolean',
        ];
    }

    public function ordenSalidaDetalle(): BelongsTo
    {
        return $this->belongsTo(OrdenSalidaDetalle::class);
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(Serial::class);
    }
}

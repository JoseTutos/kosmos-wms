<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Línea del manifiesto del BL (RS-25): qué productos autoriza y cuánto se espera. */
class BlDetalle extends Model
{
    protected $table = 'bl_detalles';

    protected $fillable = [
        'bl_id',
        'producto_id',
        'bultos_esperados',
        'unidades_esperadas',
        'peso_bruto_kg',
    ];

    protected function casts(): array
    {
        return [
            'bl_id'              => 'integer',
            'producto_id'        => 'integer',
            'bultos_esperados'   => 'integer',
            'unidades_esperadas' => 'decimal:3',
            'peso_bruto_kg'      => 'decimal:3',
        ];
    }

    public function bl(): BelongsTo
    {
        return $this->belongsTo(Bl::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Líneas de bulto recibidas de este producto en este BL (FK compuesta bl_id + producto_id).
     * Solo sobre una instancia cargada; para varias líneas use whereIn sobre BultoDetalle.
     */
    public function bultoDetalles(): HasMany
    {
        return $this->hasMany(BultoDetalle::class, 'bl_id', 'bl_id')->where('producto_id', $this->producto_id);
    }
}

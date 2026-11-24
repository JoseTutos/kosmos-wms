<?php

namespace App\Models;

use App\Support\Milesimas;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventario totalizado por triada (cliente, producto, bodega) + BL (RE-4, CUS-015: "sin cálculos en vivo").
 * Solo la escribe App\Services\Nucleo\InventarioService, en la misma transacción que el movimiento.
 */
class Existencia extends Model
{
    protected $table = 'existencias';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'cliente_id'          => 'integer',
            'producto_id'         => 'integer',
            'bodega_id'           => 'integer',
            'bl_id'               => 'integer',
            'cantidad_cuarentena' => 'decimal:3',
            'cantidad_legalizada' => 'decimal:3',
            'cantidad_reservada'  => 'decimal:3',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    public function bl(): BelongsTo
    {
        return $this->belongsTo(Bl::class);
    }

    /** /disponible = legalizada − reservada */
    protected function disponible(): Attribute
    {
        return Attribute::get(fn () => Milesimas::texto(Milesimas::de($this->cantidad_legalizada) - Milesimas::de($this->cantidad_reservada)));
    }
}

<?php

namespace App\Models;

use App\Enums\EstadoLegal;
use App\Enums\TipoMovimiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Kardex (CUS-016). Solo inserción (SEG02): no se edita ni se borra; un error se corrige con un
 * Ajuste (RS-56, RS-57). Solo lo crea App\Services\Nucleo\MovimientoService.
 */
class Movimiento extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'movimientos';

    protected $fillable = [
        'tipo',
        'bulto_detalle_id',
        'bodega_id',
        'estado_legal',
        'cantidad',
        'documento_ref',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo'             => TipoMovimiento::class,
            'bulto_detalle_id' => 'integer',
            'bodega_id'        => 'integer',
            'estado_legal'     => EstadoLegal::class,
            'cantidad'         => 'decimal:3',
            'usuario_id'       => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los movimientos son de solo inserción: corrija con un ajuste (RS-57).'));
        static::deleting(fn () => throw new LogicException('Los movimientos son de solo inserción: corrija con un ajuste (RS-57).'));
    }

    public function bultoDetalle(): BelongsTo
    {
        return $this->belongsTo(BultoDetalle::class);
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}

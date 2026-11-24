<?php

namespace App\Models;

use App\Enums\EstadoActivo;
use App\Enums\TipoAlmacenamiento;
use App\Enums\UnidadMedida;
use App\Exceptions\ReglaNegocioException;
use App\Models\Concerns\CamposInmutables;
use App\Support\Cantidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
//use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Producto «productos» (CUS-002).
 * Pertenece a un cliente ACTIVO; SKU automático y no editable.
 */
class Producto extends Model
{
    use CamposInmutables /*, LogsActivity*/;

    protected $table = 'productos';

    protected $fillable = [
        'cliente_id',
        'descripcion',
        'tipo_almacenamiento',
        'unidad_medida',
        'peso_unitario_kg',
        'stock_minimo',
    ];

    protected $attributes = [
        'estado'       => 'ACTIVO',
        'stock_minimo' => 0,
    ];

    protected function casts(): array
    {
        return [
            'tipo_almacenamiento' => TipoAlmacenamiento::class,
            'unidad_medida'       => UnidadMedida::class,
            'peso_unitario_kg'    => 'decimal:3',
            'stock_minimo'        => 'decimal:3',
            'estado'              => EstadoActivo::class,
        ];
    }

    protected function camposInmutables(): array
    {
        return ['sku', 'cliente_id'];
    }

    protected static function booted(): void
    {
        static::creating(function (Producto $producto) {
            $cliente = $producto->cliente;

            if (! $cliente || ! $cliente->estaActivo()) {
                throw new ReglaNegocioException('Solo se crean productos para clientes ACTIVOS.');
            }

            // SKU automático {CLIENTE}-{00000}
            if (blank($producto->sku)) {
                $producto->sku = $cliente->siguienteSku();
            }
        });

        static::deleting(fn () => throw new ReglaNegocioException('Los productos no se eliminan: use inactivar() (RS-04).'));
    }

    /** CUS-018: altas, cambios e inactivaciones de maestros van a la bitácora. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('maestros')
            ->logOnly(['sku', 'cliente_id', 'descripcion', 'tipo_almacenamiento', 'unidad_medida', 'peso_unitario_kg', 'stock_minimo', 'estado'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ---------------- Relaciones ----------------

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function blDetalles(): HasMany
    {
        return $this->hasMany(BlDetalle::class);
    }

    public function bultoDetalles(): HasMany
    {
        return $this->hasMany(BultoDetalle::class);
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(Existencia::class);
    }

    // ---------------- Dominio ----------------

    public function estaActivo(): bool
    {
        return $this->estado === EstadoActivo::ACTIVO;
    }

    public function inactivar(): void
    {
        $this->estado = EstadoActivo::INACTIVO;
    }

    public function activar(): void
    {
        $this->estado = EstadoActivo::ACTIVO;
    }

    /** Indicador visual de stock mínimo (RS-73). */
    public function estaBajoMinimo(string|int|float $disponible): bool
    {
        return Cantidad::comparar($disponible, $this->stock_minimo) < 0;
    }
}

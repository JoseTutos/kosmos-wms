<?php

namespace App\Models;

use App\Enums\EstadoActivo;
use App\Enums\TipoConsecutivo;
use App\Exceptions\ReglaNegocioException;
use App\Models\Concerns\CamposInmutables;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
//use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Cliente importador «clientes» (CUS-001).
 * NIT y código corto únicos e inmutables. Sin borrado físico (RS-04).
 */
class Cliente extends Model
{
    use CamposInmutables/*, LogsActivity*/;

    protected $table = 'clientes';

    protected $fillable = [
        'nit',
        'codigo_corto',
        'razon_social',
        'direccion',
        'telefono',
        'contacto_nombre',
    ];

    protected $attributes = [
        'estado' => 'ACTIVO',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoActivo::class,
        ];
    }

    protected function camposInmutables(): array
    {
        return ['nit', 'codigo_corto'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new ReglaNegocioException('Los clientes no se eliminan: use inactivar() (RS-04).'));
    }

    /** Siempre en mayúsculas (CHECK ^[A-Z]{3,6}$). */
    protected function codigoCorto(): Attribute
    {
        return Attribute::set(fn (?string $valor) => $valor === null ? null : strtoupper(trim($valor)));
    }

    /** CUS-018: altas, cambios e inactivaciones de maestros van a la bitácora. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('maestros')
            ->logOnly(['nit', 'codigo_corto', 'razon_social', 'direccion', 'telefono', 'contacto_nombre', 'estado'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ---------------- Relaciones ----------------

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function bls(): HasMany
    {
        return $this->hasMany(Bl::class);
    }

    public function ordenesSalida(): HasMany
    {
        return $this->hasMany(OrdenSalida::class);
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

    /** SKU con formato {CODIGO_CORTO}-{00000}. */
    public function generarSku(int $secuencia): string
    {
        if ($secuencia < 1 || $secuencia > 99999) {
            throw new ReglaNegocioException("Secuencia de SKU fuera de rango para {$this->codigo_corto}.");
        }

        return sprintf('%s-%05d', $this->codigo_corto, $secuencia);
    }

    public function scopeConTieneInventario(\Illuminate\Database\Eloquent\Builder $q): \Illuminate\Database\Eloquent\Builder
    {
        if (is_null($q->getQuery()->columns)) {
            $q->select('clientes.*');
        }

        return $q->selectRaw("(EXISTS (SELECT 1 FROM existencias e WHERE e.cliente_id = clientes.id AND (e.cantidad_cuarentena > 0 OR e.cantidad_legalizada > 0))
                OR EXISTS (SELECT 1 FROM bls b WHERE b.cliente_id = clientes.id AND b.estado IN ('EN_RECEPCION','RECIBIDO','DESPACHADO_PARCIAL'))) AS tiene_inventario")
            ->withCasts(['tiene_inventario' => 'boolean']);
    }
    /** Toma el siguiente consecutivo del cliente y arma el SKU (RS-07). */
    public function siguienteSku(): string
    {
        return $this->generarSku(Consecutivo::siguiente(TipoConsecutivo::SKU, $this->codigo_corto, 99999));
    }
}

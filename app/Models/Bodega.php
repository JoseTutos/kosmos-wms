<?php

namespace App\Models;

use App\Enums\EstadoActiva;
use App\Enums\EstadoBulto;
use App\Enums\EstadoPallet;
use App\Exceptions\ReglaNegocioException;
use App\Models\Concerns\CamposInmutables;
use App\Support\Cantidad;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use App\Models\Bl;
use App\Models\BlDetalle;
//use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Bodega «bodegas» (CUS-003). Medida en m², ubicaciones internas opcionales.
 */
class Bodega extends Model
{
    use CamposInmutables/*, LogsActivity*/;

    protected $table = 'bodegas';

    protected $fillable = [
        'codigo',
        'nombre',
        'tamano_m2',
        'maneja_ubicaciones',
    ];

    protected $attributes = [
        'estado'             => 'ACTIVA',
        'maneja_ubicaciones' => false,
        'm2_ocupados'        => 0,
    ];

    protected $appends = ['porcentaje_ocupacion'];

    protected function casts(): array
    {
        return [
            'tamano_m2'          => 'decimal:2',
            'maneja_ubicaciones' => 'boolean',
            'estado'             => EstadoActiva::class,
            'm2_ocupados'        => 'decimal:2',
        ];
    }

    /** El código va impreso en la etiqueta de la bodega (CUS-007): no cambia después de crearse. */
    protected function camposInmutables(): array
    {
        return ['codigo'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new ReglaNegocioException('Las bodegas no se eliminan: use inactivar().'));
    }

    /** CUS-018: altas, cambios e inactivaciones de maestros van a la bitácora. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('maestros')
            ->logOnly(['codigo', 'nombre', 'tamano_m2', 'maneja_ubicaciones', 'estado'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ---------------- Relaciones ----------------

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(Ubicacion::class);
    }

    public function pallets(): HasMany
    {
        return $this->hasMany(Pallet::class);
    }

    /** Bultos almacenados a granel (sin pallet). */
    public function bultosGranel(): HasMany
    {
        return $this->hasMany(Bulto::class);
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(Existencia::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    // ---------------- Atributos derivados ----------------

    /** /porcentaje_ocupacion = m2_ocupados / tamano_m2 × 100 */
    protected function porcentajeOcupacion(): Attribute
    {
        return Attribute::get(function () {
            $tamano = Cantidad::aMilesimas($this->tamano_m2);

            return $tamano > 0
                ? round(Cantidad::aMilesimas($this->m2_ocupados) * 100 / $tamano, 2)
                : 0.0;
        });
    }

    // ---------------- Dominio ----------------

    public function aceptaMercancia(): bool
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

    /**
     * Recalcula m2_ocupados (RS-15): pallets UBICADOS × área de pallet
     * + m² a granel de los BL con bultos activos en esta bodega.
     * Ajusta la fórmula si RS-15 define otra. El llamador hace save().
     */
    public function recalcularOcupacion(string|int|float|null $areaPallet = null): void
    {
        $area = $areaPallet ?? Configuracion::obtener('area_pallet_m2', '1.2');

        $pallets = $this->pallets()->where('estado', EstadoPallet::UBICADO->value)->count();

        $m2Granel = Bl::whereIn(
            'id',
            $this->bultosGranel()
                ->whereNotIn('estado', [EstadoBulto::DESPACHADO->value, EstadoBulto::ANULADO->value])
                ->select('bl_id')
        )->sum('m2_granel');

        $this->m2_ocupados = Cantidad::sumar(Cantidad::multiplicar($area, $pallets), $m2Granel);
    }
}

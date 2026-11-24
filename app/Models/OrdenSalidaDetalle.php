<?php

namespace App\Models;

use App\Enums\EstadoOrdenSalida;
use App\Enums\TipoLinea;
use App\Exceptions\ReglaNegocioException;
use App\Support\Cantidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Línea de la orden «orden_salida_detalles».
 * FK compuesta (bulto_detalle_id, bl_id): la línea sale del BL indicado.
 */
class OrdenSalidaDetalle extends Model
{
    protected $table = 'orden_salida_detalles';

    protected $fillable = [
        'orden_salida_id',
        'bl_id',
        'bulto_detalle_id',
        'tipo_linea',
        'cantidad_solicitada',
    ];

    protected $attributes = [
        'cantidad_despachada' => 0,
        'confirmada'          => false,
    ];

    protected function casts(): array
    {
        return [
            'tipo_linea'          => TipoLinea::class,
            'cantidad_solicitada' => 'decimal:3',
            'cantidad_despachada' => 'decimal:3',
            'confirmada'          => 'boolean',
        ];
    }

    // ---------------- Relaciones ----------------

    public function ordenSalida(): BelongsTo
    {
        return $this->belongsTo(OrdenSalida::class);
    }

    public function bl(): BelongsTo
    {
        return $this->belongsTo(Bl::class);
    }

    public function bultoDetalle(): BelongsTo
    {
        return $this->belongsTo(BultoDetalle::class);
    }

    /** Clase asociativa OrdenSalidaSerial (confirmación por serial). */
    public function seriales(): BelongsToMany
    {
        return $this->belongsToMany(Serial::class, 'orden_salida_seriales', 'orden_salida_detalle_id', 'serial_id')
            ->using(OrdenSalidaSerial::class)
            ->withPivot('confirmado')
            ->withTimestamps();
    }

    // ---------------- Dominio ----------------

    /** Confirmación por escaneo en el picking (CUS-014). */
    public function confirmar(string|int|float|null $cantidad = null): void
    {
        if ($this->confirmada) {
            throw new ReglaNegocioException('La línea ya fue confirmada.');
        }

        if ($this->ordenSalida->estado !== EstadoOrdenSalida::EN_PICKING) {
            throw new ReglaNegocioException('La orden no está EN_PICKING.');
        }

        $cantidad ??= $this->cantidad_solicitada;

        if (! Cantidad::esPositiva($cantidad)) {
            throw ReglaNegocioException::cantidadNoPositiva('Confirmar línea');
        }

        if (Cantidad::comparar($cantidad, $this->cantidad_solicitada) > 0) {
            throw ReglaNegocioException::excede('Confirmar línea', Cantidad::de($cantidad), $this->cantidad_solicitada, 'lo solicitado');
        }

        if ($this->tipo_linea === TipoLinea::COMPLETO && Cantidad::comparar($cantidad, $this->cantidad_solicitada) !== 0) {
            throw new ReglaNegocioException('Las líneas COMPLETO se despachan completas.');
        }

        if ($this->tipo_linea === TipoLinea::SERIAL) {
            $escaneados = $this->seriales()->wherePivot('confirmado', true)->count();

            if (Cantidad::comparar($escaneados, $cantidad) !== 0) {
                throw new ReglaNegocioException("Faltan seriales por escanear ({$escaneados} de {$cantidad}).");
            }
        }

        $this->cantidad_despachada = Cantidad::de($cantidad);
        $this->confirmada          = true;
    }

    public function pendiente(): string
    {
        return Cantidad::restar($this->cantidad_solicitada, $this->cantidad_despachada);
    }
}

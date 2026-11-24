<?php

namespace App\Models;

use App\Enums\EstadoOrdenSalida;
use App\Enums\TipoConsecutivo;
use App\Enums\TipoLinea;
use App\Exceptions\ReglaNegocioException;
use App\Models\Concerns\CamposInmutables;
use App\Models\Concerns\TieneEstado;
use App\Support\Cantidad;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Orden de salida «ordenes_salida» (CUS-013, CUS-014). Diagrama de estados 3.1.2 d).
 * Puede incluir varios BL; se descuenta y cierra por BL (RS-58, RS-67).
 */
class OrdenSalida extends Model
{
    use CamposInmutables, TieneEstado;

    protected $table = 'ordenes_salida';

    protected $fillable = [
        'cliente_id',
        'destinatario',
        'fecha_estimada',
        'nota_entrega_path',
    ];

    protected $attributes = [
        'estado' => 'PENDIENTE_PICKING',
    ];

    protected function casts(): array
    {
        return [
            'fecha_estimada' => 'date',
            'estado'         => EstadoOrdenSalida::class,
        ];
    }

    protected function camposInmutables(): array
    {
        return ['consecutivo', 'cliente_id'];
    }

    protected static function booted(): void
    {
        static::creating(function (OrdenSalida $orden) {
            if (! $orden->cliente?->estaActivo()) {
                throw new ReglaNegocioException('Solo se crean órdenes para clientes ACTIVOS.');
            }

            if (blank($orden->consecutivo)) {
                $orden->consecutivo = static::nuevoConsecutivo();
            }
        });
    }

    /** OS-AAAA-00000 */
    public static function nuevoConsecutivo(?int $anio = null): string
    {
        $anio ??= (int) now()->format('Y');

        return sprintf('OS-%04d-%05d', $anio, Consecutivo::siguiente(TipoConsecutivo::OS, (string) $anio, 99999));
    }

    // ---------------- Relaciones ----------------

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenSalidaDetalle::class);
    }

    public function bls(): BelongsToMany
    {
        return $this->belongsToMany(Bl::class, 'orden_salida_detalles', 'orden_salida_id', 'bl_id')->distinct();
    }

    // ---------------- Dominio ----------------

    /**
     * Crea (sin guardar) una línea. Solo mercancía LEGALIZADA del cliente (RS-59).
     * El servicio luego llama a $linea->reservar(...) en la misma transacción.
     */
    public function agregarLinea(BultoDetalle $linea, TipoLinea $tipo, string|int|float|null $cantidad = null): OrdenSalidaDetalle
    {
        if ($this->estado !== EstadoOrdenSalida::PENDIENTE_PICKING) {
            throw new ReglaNegocioException('Solo se agregan líneas a una orden PENDIENTE_PICKING.');
        }

        if ((int) $linea->bl->cliente_id !== (int) $this->cliente_id) {
            throw new ReglaNegocioException('La mercancía no pertenece al cliente de la orden.');
        }

        if ($tipo === TipoLinea::SERIAL && ! $linea->serializado) {
            throw new ReglaNegocioException('La línea no está serializada: no admite tipo SERIAL.');
        }

        $disponible = $linea->disponible;

        if (! Cantidad::esPositiva($disponible)) {
            throw new ReglaNegocioException('La línea no tiene unidades LEGALIZADAS disponibles (RS-59).');
        }

        $cantidad = $tipo === TipoLinea::COMPLETO
            ? $disponible
            : ($cantidad ?? throw new ReglaNegocioException("Las líneas {$tipo->value} requieren cantidad."));

        if (! Cantidad::esPositiva($cantidad)) {
            throw ReglaNegocioException::cantidadNoPositiva('Orden de salida');
        }

        if (Cantidad::comparar($cantidad, $disponible) > 0) {
            throw ReglaNegocioException::excede('Orden de salida', Cantidad::de($cantidad), $disponible, 'lo disponible legalizado');
        }

        if ($this->detalles()->where('bulto_detalle_id', $linea->id)->exists()) {
            throw new ReglaNegocioException('Esa línea de bulto ya está en la orden.');
        }

        return $this->detalles()->make([
            'bl_id'               => $linea->bl_id,
            'bulto_detalle_id'    => $linea->id,
            'tipo_linea'          => $tipo,
            'cantidad_solicitada' => Cantidad::de($cantidad),
        ]);
    }

    /** El servicio libera las reservas de cada línea. */
    public function anular(): void
    {
        $this->cambiarEstado(EstadoOrdenSalida::ANULADA);
    }

    public function iniciarPicking(): void
    {
        if (! $this->detalles()->exists()) {
            throw new ReglaNegocioException('La orden no tiene líneas.');
        }

        $this->cambiarEstado(EstadoOrdenSalida::EN_PICKING);
    }

    /** RS-65: todas las líneas confirmadas. */
    public function estaCompleta(): bool
    {
        return $this->detalles()->exists()
            && $this->detalles()->where('confirmada', false)->doesntExist();
    }

    public function finalizarDespacho(): void
    {
        if (! $this->estaCompleta()) {
            throw new ReglaNegocioException('Hay líneas sin confirmar: use cierre parcial (Supervisor).');
        }

        $this->cambiarEstado(EstadoOrdenSalida::DESPACHADO);
    }

    /** FA-01 CUS-014: cierre parcial autorizado por el Supervisor desde la web. */
    public function cerrarParcial(): void
    {
        if ($this->detalles()->where('confirmada', true)->doesntExist()) {
            throw new ReglaNegocioException('No hay líneas confirmadas: anule la orden en lugar de cerrarla parcialmente.');
        }

        $this->cambiarEstado(EstadoOrdenSalida::DESPACHADO_PARCIAL);
    }

    /** @return \Illuminate\Support\Collection<int, Collection<int, OrdenSalidaDetalle>> */
    public function lineasPorBl(): \Illuminate\Support\Collection
    {
        return $this->detalles()->with(['bl', 'bultoDetalle.bulto', 'bultoDetalle.producto'])->get()->groupBy('bl_id');
    }
}

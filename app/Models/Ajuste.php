<?php

namespace App\Models;

use App\Enums\EstadoLegal;
use App\Enums\MotivoAjuste;
use App\Enums\TipoAjuste;
use App\Enums\TipoConsecutivo;
use App\Exceptions\ReglaNegocioException;
use App\Models\Concerns\SoloInsercion;
use App\Support\Cantidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ajuste de inventario «ajustes» (CUS-019) — SOLO INSERCIÓN.
 * Única vía de corrección; referencia el movimiento que corrige (RS-54 a RS-57).
 */
class Ajuste extends Model
{
    use SoloInsercion;

    protected $table = 'ajustes';

    const UPDATED_AT = null;

    protected $fillable = [
        'bulto_detalle_id',
        'tipo',
        'estado_afectado',
        'cantidad',
        'motivo',
        'descripcion',
        'movimiento_corregido_id',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo'            => TipoAjuste::class,
            'estado_afectado' => EstadoLegal::class,
            'cantidad'        => 'decimal:3',
            'motivo'          => MotivoAjuste::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ajuste $ajuste) {
            if (blank($ajuste->descripcion)) {
                throw new ReglaNegocioException('El ajuste exige una descripción.');
            }

            if (blank($ajuste->consecutivo)) {
                $ajuste->consecutivo = static::nuevoConsecutivo();
            }
        });
    }

    /** AJ-AAAA-00000 */
    public static function nuevoConsecutivo(?int $anio = null): string
    {
        $anio ??= (int) now()->format('Y');

        return sprintf('AJ-%04d-%05d', $anio, Consecutivo::siguiente(TipoConsecutivo::AJ, (string) $anio, 99999));
    }

    // ---------------- Relaciones ----------------

    public function bultoDetalle(): BelongsTo
    {
        return $this->belongsTo(BultoDetalle::class);
    }

    public function movimientoCorregido(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_corregido_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // ---------------- Dominio ----------------

    /** Cantidad con signo para el movimiento AJUSTE. */
    public function cantidadConSigno(): string
    {
        return $this->tipo === TipoAjuste::NEGATIVO
            ? Cantidad::negativo($this->cantidad)
            : Cantidad::de($this->cantidad);
    }

    public function validarContra(BultoDetalle $linea): void
    {
        if ((int) $linea->id !== (int) $this->bulto_detalle_id) {
            throw new ReglaNegocioException('El ajuste no corresponde a esta línea de bulto.');
        }

        if ($this->movimiento_corregido_id !== null
            && (int) $this->movimientoCorregido?->bulto_detalle_id !== (int) $linea->id) {
            throw new ReglaNegocioException('El movimiento corregido pertenece a otra línea de bulto (RS-57).');
        }

        if ($this->tipo === TipoAjuste::NEGATIVO) {
            $limite = $this->estado_afectado === EstadoLegal::CUARENTENA ? $linea->en_cuarentena : $linea->disponible;

            if (Cantidad::comparar($this->cantidad, $limite) > 0) {
                throw ReglaNegocioException::excede('Ajuste negativo', $this->cantidad, $limite, "lo {$this->estado_afectado->value} disponible");
            }
        }
    }

    /** Valida y aplica el ajuste a la línea (el servicio guarda ambos). */
    public function aplicarA(BultoDetalle $linea): void
    {
        $this->validarContra($linea);
        $linea->ajustar($this->tipo, $this->estado_afectado, $this->cantidad);
    }
}

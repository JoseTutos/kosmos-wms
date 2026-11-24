<?php

namespace App\Models;

use App\Enums\TipoConsecutivo;
use App\Enums\TipoDocumentoLegal;
use App\Exceptions\ReglaNegocioException;
use App\Models\Concerns\SoloInsercion;
use App\Support\Cantidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Legalización «legalizaciones» (CUS-010) — SOLO INSERCIÓN (RS-48).
 */
class Legalizacion extends Model
{
    use SoloInsercion;

    protected $table = 'legalizaciones';

    const UPDATED_AT = null;

    protected $fillable = [
        'bl_id',
        'tipo_documento',
        'numero_autorizacion',
        'fecha_autorizacion',
        'soporte_path',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo_documento'     => TipoDocumentoLegal::class,
            'fecha_autorizacion' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Legalizacion $legalizacion) {
            if (blank($legalizacion->soporte_path)) {
                throw new ReglaNegocioException('El soporte de la autorización es obligatorio (RS-47).');
            }

            if (blank($legalizacion->consecutivo)) {
                $legalizacion->consecutivo = static::nuevoConsecutivo();
            }
        });
    }

    /** LEG-AAAA-00000 */
    public static function nuevoConsecutivo(?int $anio = null): string
    {
        $anio ??= (int) now()->format('Y');

        return sprintf('LEG-%04d-%05d', $anio, Consecutivo::siguiente(TipoConsecutivo::LEG, (string) $anio, 99999));
    }

    // ---------------- Relaciones ----------------

    public function bl(): BelongsTo
    {
        return $this->belongsTo(Bl::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(LegalizacionDetalle::class);
    }

    // ---------------- Dominio ----------------

    /**
     * Crea (sin guardar) el detalle del carrito. La regla de cantidad
     * la aplica BultoDetalle::legalizar(), que el servicio llama antes.
     */
    public function agregar(BultoDetalle $linea, string|int|float $cantidad): LegalizacionDetalle
    {
        if ((int) $linea->bl_id !== (int) $this->bl_id) {
            throw new ReglaNegocioException('La línea pertenece a otro BL.');
        }

        if (! Cantidad::esPositiva($cantidad)) {
            throw ReglaNegocioException::cantidadNoPositiva('Legalizar');
        }

        return $this->detalles()->make([
            'bulto_detalle_id' => $linea->id,
            'cantidad'         => Cantidad::de($cantidad),
        ]);
    }
}

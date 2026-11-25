<?php

namespace App\Models;

use App\Enums\TipoDocumentoAutorizacion;
use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Legalización LEG-AAAA-00000 (CUS-010). Cubre un solo BL (L2). Solo inserción (RS-48, L18).
 * Solo la crea LegalizacionService, dentro de su transacción.
 */
class Legalizacion extends Model
{
    use SoloInsercion;

    public const UPDATED_AT = null;

    protected $table = 'legalizaciones';

    protected $fillable = [
        'consecutivo',
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
            'bl_id'              => 'integer',
            'tipo_documento'     => TipoDocumentoAutorizacion::class,
            'fecha_autorizacion' => 'date',
            'usuario_id'         => 'integer',
            'created_at'         => 'datetime',
        ];
    }

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

    /** L19: seriales legalizados en esta LEG (para CUS-012). */
    public function seriales(): HasManyThrough
    {
        return $this->hasManyThrough(
            LegalizacionSerial::class,
            LegalizacionDetalle::class,
            'legalizacion_id',
            'legalizacion_detalle_id',
        );
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Detalle de legalización «legalizacion_detalles» — SOLO INSERCIÓN. */
class LegalizacionDetalle extends Model
{
    use SoloInsercion;

    protected $table = 'legalizacion_detalles';

    const UPDATED_AT = null;

    protected $fillable = [
        'legalizacion_id',
        'bulto_detalle_id',
        'cantidad',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
        ];
    }

    public function legalizacion(): BelongsTo
    {
        return $this->belongsTo(Legalizacion::class);
    }

    public function bultoDetalle(): BelongsTo
    {
        return $this->belongsTo(BultoDetalle::class);
    }
}

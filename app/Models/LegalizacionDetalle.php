<?php

namespace App\Models;

use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Una fila por LEG y línea de bulto (L7: la misma línea se consolida). Solo inserción. */
class LegalizacionDetalle extends Model
{
    use SoloInsercion;

    public const UPDATED_AT = null;

    protected $table = 'legalizacion_detalles';

    protected $fillable = ['legalizacion_id', 'bulto_detalle_id', 'cantidad'];

    protected function casts(): array
    {
        return [
            'legalizacion_id'  => 'integer',
            'bulto_detalle_id' => 'integer',
            'cantidad'         => 'decimal:3',
            'created_at'       => 'datetime',
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

    public function seriales(): HasMany
    {
        return $this->hasMany(LegalizacionSerial::class, 'legalizacion_detalle_id');
    }
}

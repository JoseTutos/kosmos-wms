<?php

namespace App\Models;

use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** L19: qué serial se legalizó en qué detalle de LEG. Un serial aparece una sola vez. Solo inserción. */
class LegalizacionSerial extends Model
{
    use SoloInsercion;

    public const UPDATED_AT = null;

    protected $table = 'legalizacion_seriales';

    protected $fillable = ['legalizacion_detalle_id', 'serial_id'];

    protected function casts(): array
    {
        return [
            'legalizacion_detalle_id' => 'integer',
            'serial_id'               => 'integer',
            'created_at'              => 'datetime',
        ];
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(LegalizacionDetalle::class, 'legalizacion_detalle_id');
    }

    public function serial(): BelongsTo
    {
        return $this->belongsTo(Serial::class);
    }
}

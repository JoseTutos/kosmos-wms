<?php

namespace App\Models;

use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Bitácora «auditoria» — SOLO INSERCIÓN (CUS-018), retención 5 años.
 * Mismo esquema que spatie/laravel-activitylog. Si instalas el paquete:
 *   - cambia `extends Model` por `extends \Spatie\Activitylog\Models\Activity`
 *   - en config/activitylog.php: 'table_name' => 'auditoria',
 *     'activity_model' => \App\Models\Auditoria::class
 */
class Auditoria extends Model
{
    use SoloInsercion;

    protected $table = 'auditoria';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'event',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}

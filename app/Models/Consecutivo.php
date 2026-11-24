<?php

namespace App\Models;

use App\Enums\TipoConsecutivo;
use App\Services\Nucleo\ConsecutivoService;
use Illuminate\Database\Eloquent\Model;

/**
 * Último número por (tipo, periodo). Solo lectura desde Eloquent:
 * los números los entrega App\Services\Nucleo\ConsecutivoService.
 */
class Consecutivo extends Model
{
    protected $table = 'consecutivos';

    protected $primaryKey = 'tipo'; // PK real compuesta (tipo, periodo): no use save() sobre este modelo

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'tipo'       => TipoConsecutivo::class,
            'ultimo'     => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    /** @deprecated Compatibilidad con Maestros: use ConsecutivoService. */
    public static function siguiente(TipoConsecutivo $tipo, string $periodo, int $maximo): int
    {
        return app(ConsecutivoService::class)->siguiente($tipo, $periodo, $maximo);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de una Idempotency-Key (D13). Solo lo escribe el middleware Idempotencia.
 *
 * @property int         $id
 * @property int         $usuario_id
 * @property string      $clave
 * @property string      $huella
 * @property int|null    $estado_http
 * @property string|null $cuerpo
 * @property string|null $tipo_contenido
 */
class Idempotencia extends Model
{
    use MassPrunable;

    protected $table = 'idempotencia';

    protected $fillable = ['usuario_id', 'clave', 'metodo', 'ruta', 'huella', 'estado_http', 'cuerpo', 'tipo_contenido'];

    protected function casts(): array
    {
        return ['estado_http' => 'integer'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function completada(): bool
    {
        return $this->estado_http !== null;
    }

    /** php artisan model:prune: borra las claves vencidas. */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subHours(config('kosmos.idempotencia.horas', 24)));
    }
}

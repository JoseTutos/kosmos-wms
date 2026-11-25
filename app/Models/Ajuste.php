<?php

namespace App\Models;

use App\Enums\EstadoLegal;
use App\Enums\MotivoAjuste;
use App\Enums\TipoAjuste;
use App\Models\Concerns\SoloInsercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ajuste de inventario AJ-AAAA-00000 (CUS-019). Cantidad sin signo (> 0); el tipo dice si suma o resta (J2).
 * Solo inserción: un ajuste equivocado se corrige con otro ajuste. Solo lo crea AjusteService.
 */
class Ajuste extends Model
{
    use SoloInsercion;

    public const UPDATED_AT = null;

    protected $table = 'ajustes';

    protected $fillable = [
        'consecutivo',
        'bulto_detalle_id',
        'tipo',
        'estado_afectado',
        'cantidad',
        'motivo',
        'descripcion',
        'soporte_path',
        'movimiento_corregido_id',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'bulto_detalle_id'        => 'integer',
            'tipo'                    => TipoAjuste::class,
            'estado_afectado'         => EstadoLegal::class,
            'cantidad'                => 'decimal:3',
            'motivo'                  => MotivoAjuste::class,
            'movimiento_corregido_id' => 'integer',
            'usuario_id'              => 'integer',
            'created_at'              => 'datetime',
        ];
    }

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
}

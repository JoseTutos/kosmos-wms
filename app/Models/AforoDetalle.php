<?php

namespace App\Models;

use App\Enums\CodigoError;
use App\Exceptions\ReglaNegocioException;
use App\Support\Milesimas;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Conteo de una línea de bulto dentro de un aforo. diferencia = contada − manifiesto (null si no se ha contado). */
class AforoDetalle extends Model
{
    protected $table = 'aforo_detalles';

    protected $fillable = ['aforo_id', 'bulto_detalle_id', 'cantidad_manifiesto'];

    protected function casts(): array
    {
        return [
            'aforo_id'            => 'integer',
            'bulto_detalle_id'    => 'integer',
            'cantidad_manifiesto' => 'decimal:3',
            'cantidad_contada'    => 'decimal:3',
        ];
    }

    public function aforo(): BelongsTo
    {
        return $this->belongsTo(Aforo::class);
    }

    public function bultoDetalle(): BelongsTo
    {
        return $this->belongsTo(BultoDetalle::class);
    }

    protected function diferencia(): Attribute
    {
        return Attribute::get(fn () => $this->cantidad_contada === null
            ? null
            : Milesimas::texto(Milesimas::de($this->cantidad_contada) - Milesimas::de($this->cantidad_manifiesto)));
    }

    /** Se puede corregir mientras el aforo siga EN_PROCESO. */
    public function registrarConteo(string|int $contada): void
    {
        $this->aforo->exigirEnProceso();

        $milesimas = Milesimas::de($contada);

        if ($milesimas < 0) {
            throw new ReglaNegocioException(CodigoError::CANTIDAD_INVALIDA, 'El conteo no puede ser negativo.');
        }

        $this->cantidad_contada = Milesimas::texto($milesimas);
    }
}

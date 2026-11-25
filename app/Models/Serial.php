<?php

namespace App\Models;

use App\Enums\CodigoError;
use App\Enums\EstadoLegalSerial;
use App\Enums\EstadoSerial;
use App\Exceptions\ReglaNegocioException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Serial {SKU}-{0000} de una unidad, asignado solo en el aforo (RS-44, D7b).
 * Ligado a su línea de bulto → bulto → pallet → BL → cliente.
 * CHECK: solo un serial LEGALIZADO puede dejar de estar DISPONIBLE.
 */
class Serial extends Model
{
    protected $table = 'seriales';

    protected $fillable = ['codigo', 'bulto_detalle_id'];

    protected $attributes = [
        'estado_legal' => 'EN_CUARENTENA',
        'estado'       => 'DISPONIBLE',
    ];

    protected function casts(): array
    {
        return [
            'bulto_detalle_id' => 'integer',
            'estado_legal'     => EstadoLegalSerial::class,
            'estado'           => EstadoSerial::class,
        ];
    }

    public function bultoDetalle(): BelongsTo
    {
        return $this->belongsTo(BultoDetalle::class);
    }

        /** L19: en qué LEG se legalizó (null mientras siga en cuarentena). */
    public function legalizacionSerial(): HasOne
    {
        return $this->hasOne(LegalizacionSerial::class);
    }
    // ---------------- Dominio ----------------

    public function legalizar(): void
    {
        $this->exigir($this->estado_legal === EstadoLegalSerial::EN_CUARENTENA);
        $this->estado_legal = EstadoLegalSerial::LEGALIZADO;
    }

    public function reservar(): void
    {
        $this->exigir($this->estado_legal === EstadoLegalSerial::LEGALIZADO && $this->estado === EstadoSerial::DISPONIBLE);
        $this->estado = EstadoSerial::RESERVADO;
    }

    public function liberar(): void
    {
        $this->exigir($this->estado === EstadoSerial::RESERVADO);
        $this->estado = EstadoSerial::DISPONIBLE;
    }

    public function despachar(): void
    {
        $this->exigir($this->estado === EstadoSerial::RESERVADO);
        $this->estado = EstadoSerial::DESPACHADO;
    }

    private function exigir(bool $condicion): void
    {
        if (! $condicion) {
            throw new ReglaNegocioException(CodigoError::SERIAL_ESTADO_INVALIDO, datos: [
                'serial' => $this->codigo, 'estado' => $this->estado->value, 'estado_legal' => $this->estado_legal->value,
            ]);
        }
    }
}

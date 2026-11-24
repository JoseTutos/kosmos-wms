<?php

namespace App\Models;

use App\Enums\CodigoError;
use App\Exceptions\ReglaNegocioException;
use App\Support\Milesimas;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Línea de bulto: un producto y sus cuatro cantidades base (diseño 3.1.1-1).
 * Derivadas: en_cuarentena = recibida − legalizada · disponible = legalizada − reservada − despachada.
 * Las reglas de cantidad viven aquí (RS-45, RS-55, RS-60); los CHECK de la BD las refuerzan.
 * Los métodos no persisten: el service hace save() y registra el movimiento.
 */
class BultoDetalle extends Model
{
    protected $table = 'bulto_detalles';

    protected $fillable = ['bulto_id', 'bl_id', 'producto_id', 'cantidad_recibida', 'serializado'];

    protected $attributes = [
        'cantidad_legalizada' => 0,
        'cantidad_reservada'  => 0,
        'cantidad_despachada' => 0,
        'serializado'         => false,
    ];

    protected function casts(): array
    {
        return [
            'bulto_id'            => 'integer',
            'bl_id'               => 'integer',
            'producto_id'         => 'integer',
            'cantidad_recibida'   => 'decimal:3',
            'cantidad_legalizada' => 'decimal:3',
            'cantidad_reservada'  => 'decimal:3',
            'cantidad_despachada' => 'decimal:3',
            'serializado'         => 'boolean',
        ];
    }

    // ---------------- Relaciones ----------------

    public function bulto(): BelongsTo
    {
        return $this->belongsTo(Bulto::class);
    }

    public function bl(): BelongsTo
    {
        return $this->belongsTo(Bl::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function seriales(): HasMany
    {
        return $this->hasMany(Serial::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    public function aforoDetalles(): HasMany
    {
        return $this->hasMany(AforoDetalle::class);
    }

    // ---------------- Derivados ----------------

    protected function enCuarentena(): Attribute
    {
        return Attribute::get(fn () => Milesimas::texto($this->m('cantidad_recibida') - $this->m('cantidad_legalizada')));
    }

    protected function disponible(): Attribute
    {
        return Attribute::get(fn () => Milesimas::texto(
            $this->m('cantidad_legalizada') - $this->m('cantidad_reservada') - $this->m('cantidad_despachada')
        ));
    }

    // ---------------- Dominio ----------------

    /** CUS-010: pasa de cuarentena a legalizada. */
    public function legalizar(string|int $cantidad): void
    {
        $q = $this->positiva($cantidad);
        $this->exigirHasta($q, $this->m('cantidad_recibida') - $this->m('cantidad_legalizada'), 'en_cuarentena');

        $this->fijar('cantidad_legalizada', $this->m('cantidad_legalizada') + $q);
    }

    /** CUS-013: reserva sobre lo disponible (legalizado y no comprometido). */
    public function reservar(string|int $cantidad): void
    {
        $q = $this->positiva($cantidad);
        $this->exigirHasta($q, Milesimas::de($this->disponible), 'disponible');

        $this->fijar('cantidad_reservada', $this->m('cantidad_reservada') + $q);
    }

    public function liberarReserva(string|int $cantidad): void
    {
        $q = $this->positiva($cantidad);
        $this->exigirHasta($q, $this->m('cantidad_reservada'), 'reservada');

        $this->fijar('cantidad_reservada', $this->m('cantidad_reservada') - $q);
    }

    /** CUS-014: solo se despacha lo reservado. */
    public function despachar(string|int $cantidad): void
    {
        $q = $this->positiva($cantidad);
        $this->exigirHasta($q, $this->m('cantidad_reservada'), 'reservada');

        $this->fijar('cantidad_reservada', $this->m('cantidad_reservada') - $q);
        $this->fijar('cantidad_despachada', $this->m('cantidad_despachada') + $q);
    }

    /**
     * CUS-009 / D2: el conteo real reemplaza la cantidad recibida.
     * Devuelve la diferencia (contada − recibida anterior) para el movimiento AFORO; "0.000" si coincide.
     */
    public function aplicarConteo(string|int $contada): string
    {
        $nueva = Milesimas::de($contada);

        if ($nueva <= 0) {
            throw new ReglaNegocioException(
                CodigoError::CANTIDAD_INVALIDA,
                'Un conteo en 0 no puede aplicarse a la línea: registre un ajuste (CUS-019).',
                $this->contexto(),
            );
        }

        $comprometida = max($this->m('cantidad_legalizada'), $this->m('cantidad_reservada') + $this->m('cantidad_despachada'));

        if ($nueva < $comprometida) {
            throw new ReglaNegocioException(CodigoError::CONTEO_MENOR_A_LEGALIZADO, datos: $this->contexto() + [
                'contada' => Milesimas::texto($nueva), 'legalizada' => $this->cantidad_legalizada,
            ]);
        }

        $diferencia = $nueva - $this->m('cantidad_recibida');
        $this->fijar('cantidad_recibida', $nueva);

        return Milesimas::texto($diferencia);
    }

    // ---------------- Apoyo ----------------

    private function m(string $columna): int
    {
        return Milesimas::de($this->getAttribute($columna));
    }

    private function fijar(string $columna, int $milesimas): void
    {
        $this->setAttribute($columna, Milesimas::texto($milesimas));
    }

    private function positiva(string|int $cantidad): int
    {
        $q = Milesimas::de($cantidad);

        if ($q <= 0) {
            throw new ReglaNegocioException(CodigoError::CANTIDAD_INVALIDA, datos: $this->contexto());
        }

        return $q;
    }

    private function exigirHasta(int $solicitada, int $limite, string $que): void
    {
        if ($solicitada > $limite) {
            throw new ReglaNegocioException(CodigoError::CANTIDAD_INSUFICIENTE, datos: $this->contexto() + [
                'solicitada' => Milesimas::texto($solicitada), $que => Milesimas::texto($limite),
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function contexto(): array
    {
        return ['linea' => $this->getKey(), 'producto_id' => $this->producto_id];
    }
}

<?php

namespace App\Models;

use App\Enums\CodigoError;
use App\Enums\EstadoLegal;
use App\Enums\EstadoLegalSerial;
use App\Enums\TipoAjuste;
use App\Exceptions\ReglaNegocioException;
use App\Support\Milesimas;
use BackedEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Línea de bulto: un producto y sus cuatro cantidades base (diseño 3.1.1-1).
 * Derivadas: en_cuarentena = recibida − legalizada · disponible = legalizada − reservada − despachada.
 * Las reglas de cantidad viven aquí (RS-45, RS-46, RS-55, RS-60, J2, J3); los CHECK de la BD las refuerzan.
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

    public function legalizacionDetalles(): HasMany
    {
        return $this->hasMany(LegalizacionDetalle::class);
    }

    public function ajustes(): HasMany
    {
        return $this->hasMany(Ajuste::class);
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

    /** Pasa de cuarentena a legalizada, sin mirar el modo. CUS-010 usa legalizarPorCantidad() o legalizarSeriales(). */
    public function legalizar(string|int $cantidad): void
    {
        $q = $this->positiva($cantidad);
        $this->exigirHasta($q, $this->m('cantidad_recibida') - $this->m('cantidad_legalizada'), 'en_cuarentena');

        $this->fijar('cantidad_legalizada', $this->m('cantidad_legalizada') + $q);
    }

    /**
     * CUS-010 Modo B (L5, RS-46): solo líneas no serializadas. UND entero; KG y LT hasta 3 decimales.
     * Una línea serializada se legaliza completa o por seriales (L4, L6).
     *
     * @return string cantidad legalizada ("0.000") para el detalle y el movimiento
     */
    public function legalizarPorCantidad(string|int $cantidad): string
    {
        if ($this->serializado) {
            throw new ReglaNegocioException(CodigoError::LINEA_SERIALIZADA, datos: $this->contexto());
        }

        $q = $this->positiva($cantidad);
        $this->exigirFraccionPermitida($q);
        $this->exigirCabeEnCuarentena($q);

        $this->fijar('cantidad_legalizada', $this->m('cantidad_legalizada') + $q);

        return Milesimas::texto($q);
    }

    /**
     * CUS-010 Modo A en línea serializada y Modo C (L4, L6): legaliza exactamente esos seriales.
     * Valida todos antes de cambiar nada: o pasan todos o no pasa ninguno.
     * El service los trae bloqueados (FOR UPDATE) y hace save() de la línea y de cada serial.
     *
     * @param iterable<Serial> $seriales
     * @return string cantidad legalizada (1 por serial)
     */
    public function legalizarSeriales(iterable $seriales): string
    {
        if (! $this->serializado) {
            throw new ReglaNegocioException(CodigoError::LINEA_NO_SERIALIZADA, datos: $this->contexto());
        }

        $validos = [];

        foreach ($seriales as $serial) {
            $datos = $this->contexto() + ['serial' => $serial->codigo];

            if ((int) $serial->bulto_detalle_id !== (int) $this->getKey()) {
                throw new ReglaNegocioException(CodigoError::SERIAL_OTRA_LINEA, datos: $datos);
            }
            if (isset($validos[$serial->codigo])) {
                throw new ReglaNegocioException(CodigoError::SERIAL_REPETIDO, datos: $datos);
            }
            if ($serial->estado_legal !== EstadoLegalSerial::EN_CUARENTENA) {
                throw new ReglaNegocioException(CodigoError::SERIAL_YA_LEGALIZADO, datos: $datos);
            }

            $validos[$serial->codigo] = $serial;
        }

        if ($validos === []) {
            throw new ReglaNegocioException(CodigoError::CANTIDAD_INVALIDA, datos: $this->contexto());
        }

        $q = count($validos) * 1000;
        $this->exigirCabeEnCuarentena($q);

        $this->fijar('cantidad_legalizada', $this->m('cantidad_legalizada') + $q);

        foreach ($validos as $serial) {
            $serial->legalizar();
        }

        return Milesimas::texto($q);
    }

    /**
     * CUS-019 (J2, J3): CUARENTENA mueve la recibida; LEGALIZADA mueve la recibida y la legalizada.
     * Nunca deja negativos (EX-01) ni baja la legalizada de reservada + despachada (EX-02, RS-55).
     *
     * @return string cantidad con signo para el movimiento AJUSTE
     */
    public function ajustar(TipoAjuste $tipo, string|int $cantidad, EstadoLegal $estado): string
    {
        $q = $this->positiva($cantidad);
        $this->exigirFraccionPermitida($q);

        $recibida = $this->m('cantidad_recibida');
        $legalizada = $this->m('cantidad_legalizada');

        if ($tipo === TipoAjuste::NEGATIVO) {
            $limite = $estado === EstadoLegal::CUARENTENA ? $recibida - $legalizada : $legalizada;

            if ($q > $limite) {
                throw new ReglaNegocioException(CodigoError::AJUSTE_DEJA_NEGATIVO, datos: $this->contexto() + [
                    'solicitada' => Milesimas::texto($q), 'estado' => $estado->value, 'actual' => Milesimas::texto($limite),
                ]);
            }

            $comprometida = $this->m('cantidad_reservada') + $this->m('cantidad_despachada');

            if ($estado === EstadoLegal::LEGALIZADA && $legalizada - $q < $comprometida) {
                throw new ReglaNegocioException(CodigoError::AJUSTE_AFECTA_RESERVADAS, datos: $this->contexto() + [
                    'solicitada' => Milesimas::texto($q), 'disponible' => Milesimas::texto($legalizada - $comprometida),
                ]);
            }
        }

        $delta = $tipo->signo() * $q;

        $this->fijar('cantidad_recibida', $recibida + $delta);

        if ($estado === EstadoLegal::LEGALIZADA) {
            $this->fijar('cantidad_legalizada', $legalizada + $delta);
        }

        return Milesimas::texto($delta);
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

    /** FA-04: no se legaliza más de lo que está en cuarentena. */
    private function exigirCabeEnCuarentena(int $q): void
    {
        $cuarentena = $this->m('cantidad_recibida') - $this->m('cantidad_legalizada');

        if ($q > $cuarentena) {
            throw new ReglaNegocioException(CodigoError::CANTIDAD_SUPERA_CUARENTENA, datos: $this->contexto() + [
                'solicitada' => Milesimas::texto($q), 'en_cuarentena' => Milesimas::texto($cuarentena),
            ]);
        }
    }

    /** RS-46: UND solo admite enteros; KG y LT, hasta 3 decimales (ya garantizado por Milesimas). */
    private function exigirFraccionPermitida(int $q): void
    {
        $unidad = $this->producto?->unidad_medida;
        $unidad = $unidad instanceof BackedEnum ? $unidad->value : $unidad;

        if ($unidad === 'UND' && $q % 1000 !== 0) {
            throw new ReglaNegocioException(CodigoError::CANTIDAD_NO_ENTERA, datos: $this->contexto() + [
                'solicitada' => Milesimas::texto($q),
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function contexto(): array
    {
        return ['linea' => $this->getKey(), 'producto_id' => $this->producto_id];
    }
}

<?php

namespace App\Models;

use App\Enums\CodigoError;
use App\Enums\EstadoBl;
use App\Enums\EstadoBulto;
use App\Exceptions\ReglaNegocioException;
use App\Support\Milesimas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bill of Lading (CUS-005/006). Estados (3.1.2):
 * PROGRAMADO → EN_RECEPCION → RECIBIDO → DESPACHADO_PARCIAL → CERRADO (RECIBIDO → CERRADO también).
 * La recepción no se reabre (D4).
 */
class Bl extends Model
{
    protected $table = 'bls';

    protected $fillable = [
        'numero_bl',
        'cliente_id',
        'fecha_ingreso',
        'factura_comercial',
        'fecha_preinspeccion',
        'acta_preinspeccion_path',
    ];

    protected $attributes = [
        'estado' => 'PROGRAMADO',
    ];

    private const TRANSICIONES = [
        'PROGRAMADO'         => ['EN_RECEPCION'],
        'EN_RECEPCION'       => ['RECIBIDO'],
        'RECIBIDO'           => ['DESPACHADO_PARCIAL', 'CERRADO'],
        'DESPACHADO_PARCIAL' => ['CERRADO'],
        'CERRADO'            => [],
    ];

    protected function casts(): array
    {
        return [
            'cliente_id'          => 'integer',
            'fecha_ingreso'       => 'date',
            'fecha_preinspeccion' => 'date',
            'estado'              => EstadoBl::class,
            'discrepancia_bultos' => 'integer',
            'm2_granel'           => 'decimal:2', // sin uso en el MVP (D7)
        ];
    }

    // ---------------- Relaciones ----------------

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(BlDetalle::class);
    }

    public function pallets(): HasMany
    {
        return $this->hasMany(Pallet::class);
    }

    public function bultos(): HasMany
    {
        return $this->hasMany(Bulto::class);
    }

    public function bultoDetalles(): HasMany
    {
        return $this->hasMany(BultoDetalle::class);
    }

    public function aforos(): HasMany
    {
        return $this->hasMany(Aforo::class);
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(Existencia::class);
    }

      public function legalizaciones(): HasMany
    {
        return $this->hasMany(Legalizacion::class);
    }

    // ---------------- Dominio ----------------

    /** CUS-005: el BL y su manifiesto solo se editan o eliminan mientras está PROGRAMADO. */
    public function esEditable(): bool
    {
        return $this->estado === EstadoBl::PROGRAMADO;
    }

    public function estaEnRecepcion(): bool
    {
        return $this->estado === EstadoBl::EN_RECEPCION;
    }

    public function exigirEnRecepcion(): void
    {
        if (! $this->estaEnRecepcion()) {
            throw new ReglaNegocioException(CodigoError::BL_NO_EN_RECEPCION, "El BL {$this->numero_bl} no está en recepción.", [
                'bl' => $this->numero_bl, 'estado' => $this->estado->value,
            ]);
        }
    }

    public function iniciarRecepcion(): void
    {
        $this->transicionar(EstadoBl::EN_RECEPCION);
    }

    /** Diferencia de bultos contra el manifiesto: positiva = sobran, negativa = faltan (D6). */
    public function registrarDiscrepancia(int $esperados, int $recibidos): void
    {
        $this->discrepancia_bultos = $recibidos - $esperados;
    }

    /**
     * Cierra la recepción (D3, D4). Cualquier diferencia exige observación (RS-33, D5, D6).
     * Las reglas que cruzan entidades (todos los bultos en pallet, D7) las valida RecepcionService.
     *
     * @param bool $hayNovedades  otras diferencias detectadas por el service (unidades, peso fuera de tolerancia)
     */
    public function finalizarRecepcion(?string $observacion, bool $hayNovedades = false): void
    {
        $this->exigirEnRecepcion();

        $observacion = trim((string) $observacion);
        $hayDiferencias = $hayNovedades || ($this->discrepancia_bultos ?? 0) !== 0;

        if ($observacion === '' && $hayDiferencias) {
            throw new ReglaNegocioException(CodigoError::OBSERVACION_REQUERIDA, datos: [
                'bl' => $this->numero_bl, 'discrepancia_bultos' => $this->discrepancia_bultos,
            ], campo: 'observacion_recepcion');
        }

        $this->observacion_recepcion = $observacion === '' ? null : $observacion;
        $this->transicionar(EstadoBl::RECIBIDO);
    }

    /** Tras un despacho (CUS-014): DESPACHADO_PARCIAL o CERRADO según lo despachado. No cuenta bultos anulados. */
    public function actualizarSaldo(): void
    {
        if (! in_array($this->estado, [EstadoBl::RECIBIDO, EstadoBl::DESPACHADO_PARCIAL], true)) {
            return;
        }

        $totales = $this->bultoDetalles()->toBase()
            ->whereIn('bulto_id', Bulto::query()->select('id')
                ->where('bl_id', $this->getKey())
                ->where('estado', '!=', EstadoBulto::ANULADO->value))
            ->selectRaw('COALESCE(SUM(cantidad_recibida), 0) AS recibida, COALESCE(SUM(cantidad_despachada), 0) AS despachada')
            ->first();

        $despachada = Milesimas::de((string) $totales->despachada);

        if ($despachada === 0) {
            return;
        }

        $nuevo = $despachada >= Milesimas::de((string) $totales->recibida) ? EstadoBl::CERRADO : EstadoBl::DESPACHADO_PARCIAL;

        if ($nuevo !== $this->estado) {
            $this->transicionar($nuevo);
        }
    }

    private function transicionar(EstadoBl $nuevo): void
    {
        if (! in_array($nuevo->value, self::TRANSICIONES[$this->estado->value] ?? [], true)) {
            throw new ReglaNegocioException(
                CodigoError::BL_ESTADO_INVALIDO,
                "El BL {$this->numero_bl} no puede pasar de {$this->estado->value} a {$nuevo->value}.",
                ['bl' => $this->numero_bl, 'de' => $this->estado->value, 'a' => $nuevo->value],
            );
        }

        $this->estado = $nuevo;
    }
}

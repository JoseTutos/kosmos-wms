<?php

namespace App\Models;

use App\Enums\CodigoError;
use App\Enums\EstadoBulto;
use App\Enums\EstadoPallet;
use App\Exceptions\ReglaNegocioException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pallet PLT-DDMMAA-0000 (CUS-006/008). Estados:
 * ABIERTO ⇄ CERRADO → UBICADO (→ UBICADO en traslado) → DESPACHADO.
 * Los métodos no persisten: el service hace save() dentro de su transacción.
 */
class Pallet extends Model
{
    protected $table = 'pallets';

    protected $fillable = ['codigo', 'bl_id'];

    protected $attributes = [
        'estado' => 'ABIERTO',
    ];

    private const TRANSICIONES = [
        'ABIERTO'    => ['CERRADO'],
        'CERRADO'    => ['ABIERTO', 'UBICADO'],
        'UBICADO'    => ['UBICADO', 'DESPACHADO'],
        'DESPACHADO' => [],
    ];

    protected function casts(): array
    {
        return [
            'bl_id'        => 'integer',
            'bodega_id'    => 'integer',
            'ubicacion_id' => 'integer',
            'estado'       => EstadoPallet::class,
        ];
    }

    // ---------------- Relaciones ----------------

    public function bl(): BelongsTo
    {
        return $this->belongsTo(Bl::class);
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function bultos(): HasMany
    {
        return $this->hasMany(Bulto::class);
    }

    // ---------------- Dominio ----------------

    public function estaAbierto(): bool
    {
        return $this->estado === EstadoPallet::ABIERTO;
    }

    public function estaVacio(): bool
    {
        return ! $this->bultos()->where('estado', '!=', EstadoBulto::ANULADO->value)->exists();
    }

    /** D7: el bulto debe ser del mismo BL y no estar en otro pallet. El llamador hace $bulto->save(). */
    public function agregarBulto(Bulto $bulto): void
    {
        $this->exigirAbierto();

        if ($bulto->bl_id !== $this->bl_id) {
            throw new ReglaNegocioException(CodigoError::BULTO_OTRO_BL, datos: ['bulto' => $bulto->codigo, 'pallet' => $this->codigo]);
        }
        if ($bulto->estado === EstadoBulto::ANULADO) {
            throw new ReglaNegocioException(CodigoError::BULTO_ANULADO, datos: ['bulto' => $bulto->codigo]);
        }
        if ($bulto->pallet_id !== null && $bulto->pallet_id !== $this->getKey()) {
            throw new ReglaNegocioException(CodigoError::BULTO_EN_OTRO_PALLET, datos: ['bulto' => $bulto->codigo]);
        }

        $bulto->pallet_id = $this->getKey();
        $bulto->bodega_id = null; // CHECK: un bulto está en un pallet o en una bodega, no en ambos
    }

    public function retirarBulto(Bulto $bulto): void
    {
        $this->exigirAbierto();

        if ($bulto->pallet_id !== $this->getKey()) {
            throw new ReglaNegocioException(CodigoError::BULTO_NO_EN_PALLET, datos: ['bulto' => $bulto->codigo, 'pallet' => $this->codigo]);
        }

        $bulto->pallet_id = null;
    }

    public function cerrar(): void
    {
        $this->exigirAbierto();

        if ($this->estaVacio()) {
            throw new ReglaNegocioException(CodigoError::PALLET_VACIO, datos: ['pallet' => $this->codigo]);
        }

        $this->transicionar(EstadoPallet::CERRADO);
    }

    public function reabrir(): void
    {
        $this->transicionar(EstadoPallet::ABIERTO);
    }

    /**
     * CUS-008: ubica (CERRADO) o traslada (UBICADO). D10: no exige que el BL esté RECIBIDO.
     * Devuelve la bodega anterior (null si es la primera ubicación) para el movimiento TRASLADO.
     */
    public function ubicar(Bodega $bodega, ?Ubicacion $ubicacion = null, ?string $referencia = null): ?int
    {
        if (! in_array($this->estado, [EstadoPallet::CERRADO, EstadoPallet::UBICADO], true)) {
            throw new ReglaNegocioException(CodigoError::PALLET_ESTADO_INVALIDO, 'Solo se ubica un pallet cerrado.', [
                'pallet' => $this->codigo, 'estado' => $this->estado->value,
            ]);
        }
        if (! $bodega->aceptaMercancia()) {
            throw new ReglaNegocioException(CodigoError::BODEGA_INACTIVA, datos: ['bodega' => $bodega->codigo]);
        }

        if ($bodega->maneja_ubicaciones) {
            if (! $ubicacion) {
                throw new ReglaNegocioException(CodigoError::UBICACION_REQUERIDA, datos: ['bodega' => $bodega->codigo]);
            }
            if ((int) $ubicacion->bodega_id !== (int) $bodega->getKey()) {
                throw new ReglaNegocioException(CodigoError::UBICACION_OTRA_BODEGA, datos: ['ubicacion' => $ubicacion->codigo, 'bodega' => $bodega->codigo]);
            }
            if (! $ubicacion->estaActiva()) {
                throw new ReglaNegocioException(CodigoError::UBICACION_INACTIVA, datos: ['ubicacion' => $ubicacion->codigo]);
            }
        } elseif ($ubicacion) {
            throw new ReglaNegocioException(CodigoError::UBICACION_NO_PERMITIDA, datos: ['bodega' => $bodega->codigo]);
        }

        $anterior = $this->bodega_id;

        $this->bodega_id = (int) $bodega->getKey();
        $this->ubicacion_id = $ubicacion?->getKey();
        $this->referencia_ubicacion = filled($referencia) ? mb_substr(trim($referencia), 0, 150) : null;
        $this->transicionar(EstadoPallet::UBICADO);

        return $anterior;
    }

    private function exigirAbierto(): void
    {
        if (! $this->estaAbierto()) {
            throw new ReglaNegocioException(CodigoError::PALLET_CERRADO, datos: ['pallet' => $this->codigo, 'estado' => $this->estado->value]);
        }
    }

    private function transicionar(EstadoPallet $nuevo): void
    {
        if (! in_array($nuevo->value, self::TRANSICIONES[$this->estado->value] ?? [], true)) {
            throw new ReglaNegocioException(
                CodigoError::PALLET_ESTADO_INVALIDO,
                "El pallet {$this->codigo} no puede pasar de {$this->estado->value} a {$nuevo->value}.",
                ['pallet' => $this->codigo, 'de' => $this->estado->value, 'a' => $nuevo->value],
            );
        }

        $this->estado = $nuevo;
    }
}

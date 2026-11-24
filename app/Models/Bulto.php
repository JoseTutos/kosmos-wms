<?php

namespace App\Models;

use App\Enums\CodigoError;
use App\Enums\EstadoBulto;
use App\Enums\EstadoPallet;
use App\Exceptions\ReglaNegocioException;
use App\Support\Milesimas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Bulto BUL-DDMMAA-0000 (CUS-006). Puede ser mixto: una línea por producto (RS-29, D7a).
 * Estados: RECIBIDO → UBICADO · (RECIBIDO|UBICADO) → EN_AFORO → AFORADO · → DESPACHADO · RECIBIDO → ANULADO.
 */
class Bulto extends Model
{
    protected $table = 'bultos';

    protected $fillable = ['codigo', 'bl_id', 'peso_bruto_kg'];

    protected $attributes = [
        'estado' => 'RECIBIDO',
    ];

    private const TRANSICIONES = [
        'RECIBIDO'   => ['UBICADO', 'EN_AFORO', 'ANULADO', 'DESPACHADO'],
        'UBICADO'    => ['EN_AFORO', 'DESPACHADO'],
        'EN_AFORO'   => ['AFORADO', 'RECIBIDO', 'UBICADO'], // cerrar o cancelar el aforo
        'AFORADO'    => ['DESPACHADO'],
        'DESPACHADO' => [],
        'ANULADO'    => [],
    ];

    protected function casts(): array
    {
        return [
            'bl_id'         => 'integer',
            'pallet_id'     => 'integer',
            'bodega_id'     => 'integer',
            'peso_bruto_kg' => 'decimal:3',
            'estado'        => EstadoBulto::class,
        ];
    }

    // ---------------- Relaciones ----------------

    public function bl(): BelongsTo
    {
        return $this->belongsTo(Bl::class);
    }

    public function pallet(): BelongsTo
    {
        return $this->belongsTo(Pallet::class);
    }

    /** Sin uso en el MVP: todo bulto va en un pallet (D7). */
    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(BultoDetalle::class);
    }

    // ---------------- Dominio ----------------

    /**
     * Nueva línea (sin guardar). Solo productos del manifiesto del BL (RS-25) y uno por bulto.
     * El bulto debe estar guardado y RECIBIDO. El llamador hace $linea->save().
     */
    public function agregarLinea(Producto $producto, string|int $cantidad): BultoDetalle
    {
        if (! $this->exists) {
            throw new LogicException('Guarde el bulto antes de agregarle líneas.');
        }

        $this->exigirEstado(EstadoBulto::RECIBIDO);

        $milesimas = Milesimas::de($cantidad);

        if ($milesimas <= 0) {
            throw new ReglaNegocioException(CodigoError::CANTIDAD_INVALIDA, datos: ['sku' => $producto->sku]);
        }
        if (! BlDetalle::where('bl_id', $this->bl_id)->where('producto_id', $producto->getKey())->exists()) {
            throw new ReglaNegocioException(CodigoError::PRODUCTO_NO_EN_BL, datos: ['sku' => $producto->sku]);
        }
        if ($this->detalles()->where('producto_id', $producto->getKey())->exists()) {
            throw new ReglaNegocioException(CodigoError::PRODUCTO_REPETIDO_EN_BULTO, datos: ['sku' => $producto->sku, 'bulto' => $this->codigo]);
        }

        return (new BultoDetalle)->forceFill([
            'bulto_id'          => $this->getKey(),
            'bl_id'             => $this->bl_id,
            'producto_id'       => $producto->getKey(),
            'cantidad_recibida' => Milesimas::texto($milesimas),
        ]);
    }

    /** Solo un bulto RECIBIDO. Si está en un pallet, el pallet debe estar ABIERTO; sale del pallet. */
    public function anular(): void
    {
        $this->exigirEstado(EstadoBulto::RECIBIDO);

        if ($this->pallet_id !== null) {
            if ($this->pallet && $this->pallet->estado !== EstadoPallet::ABIERTO) {
                throw new ReglaNegocioException(CodigoError::PALLET_CERRADO, datos: ['pallet' => $this->pallet->codigo, 'bulto' => $this->codigo]);
            }

            $this->pallet_id = null;
        }

        $this->transicionar(EstadoBulto::ANULADO);
    }

    /** Al ubicar su pallet. Un bulto en aforo o aforado conserva su estado. */
    public function marcarUbicado(): void
    {
        if ($this->estado === EstadoBulto::RECIBIDO) {
            $this->transicionar(EstadoBulto::UBICADO);
        } elseif (! in_array($this->estado, [EstadoBulto::UBICADO, EstadoBulto::EN_AFORO, EstadoBulto::AFORADO], true)) {
            $this->exigirEstado(EstadoBulto::RECIBIDO);
        }
    }

    public function iniciarAforo(): void
    {
        $this->transicionar(EstadoBulto::EN_AFORO);
    }

    public function cerrarAforo(): void
    {
        $this->exigirEstado(EstadoBulto::EN_AFORO);
        $this->transicionar(EstadoBulto::AFORADO);
    }

    /** Vuelve a UBICADO si su pallet está ubicado; si no, a RECIBIDO. */
    public function cancelarAforo(): void
    {
        $this->exigirEstado(EstadoBulto::EN_AFORO);
        $this->transicionar($this->pallet?->estado === EstadoPallet::UBICADO ? EstadoBulto::UBICADO : EstadoBulto::RECIBIDO);
    }

    private function exigirEstado(EstadoBulto $esperado): void
    {
        if ($this->estado !== $esperado) {
            throw new ReglaNegocioException(
                $this->estado === EstadoBulto::ANULADO ? CodigoError::BULTO_ANULADO : CodigoError::BULTO_ESTADO_INVALIDO,
                datos: ['bulto' => $this->codigo, 'estado' => $this->estado->value],
            );
        }
    }

    private function transicionar(EstadoBulto $nuevo): void
    {
        if (! in_array($nuevo->value, self::TRANSICIONES[$this->estado->value] ?? [], true)) {
            throw new ReglaNegocioException(
                $this->estado === EstadoBulto::ANULADO ? CodigoError::BULTO_ANULADO : CodigoError::BULTO_ESTADO_INVALIDO,
                "El bulto {$this->codigo} no puede pasar de {$this->estado->value} a {$nuevo->value}.",
                ['bulto' => $this->codigo, 'de' => $this->estado->value, 'a' => $nuevo->value],
            );
        }

        $this->estado = $nuevo;
    }
}

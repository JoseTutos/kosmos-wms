<?php

namespace App\Services\Nucleo;

use App\Enums\CodigoError;
use App\Enums\EstadoLegal;
use App\Enums\TipoMovimiento;
use App\Exceptions\ReglaNegocioException;
use App\Models\BultoDetalle;
use App\Models\Movimiento;
use App\Models\User;
use App\Support\Milesimas;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Único que inserta en movimientos (kardex de solo inserción, SEG02) y, en la misma
 * transacción, actualiza existencias por medio de InventarioService.
 *
 * Bodega null = mercancía aún sin ubicar: el movimiento queda en el kardex pero no suma
 * existencia de bodega (existencias.bodega_id es obligatorio). Al ubicar el pallet, trasladar()
 * con origen null la lleva a la bodega y el saldo del kardex por línea se mantiene.
 */
final class MovimientoService
{
    public function __construct(private readonly InventarioService $inventario) {}

    /**
     * @param string|int $cantidad  con signo: positiva entra, negativa sale (nunca 0)
     */
    public function registrar(
        TipoMovimiento $tipo,
        BultoDetalle $linea,
        string|int $cantidad,
        ?int $bodegaId,
        ?EstadoLegal $estadoLegal,
        User $usuario,
        ?string $documentoRef = null,
    ): Movimiento {
        $this->exigirTransaccion();

        $milesimas = Milesimas::de($cantidad);

        if ($milesimas === 0) {
            throw new ReglaNegocioException(CodigoError::CANTIDAD_INVALIDA, 'Un movimiento no puede tener cantidad 0.');
        }
        if ($bodegaId !== null && $estadoLegal === null) {
            throw new LogicException('Un movimiento con bodega debe indicar el estado legal (CUARENTENA o LEGALIZADA).');
        }
        if ($documentoRef !== null && mb_strlen($documentoRef) > 20) {
            throw new LogicException('documento_ref admite máximo 20 caracteres.');
        }

        $movimiento = Movimiento::create([
            'tipo'             => $tipo,
            'bulto_detalle_id' => $linea->getKey(),
            'bodega_id'        => $bodegaId,
            'estado_legal'     => $estadoLegal,
            'cantidad'         => Milesimas::texto($milesimas),
            'documento_ref'    => $documentoRef,
            'usuario_id'       => $usuario->getKey(),
        ]);

        if ($bodegaId !== null) {
            $this->inventario->aplicar($linea, $bodegaId, $estadoLegal, $milesimas);
        }

        return $movimiento;
    }

    /**
     * Traslado = dos movimientos TRASLADO: salida del origen (−) y entrada al destino (+).
     * Origen null = primera ubicación de mercancía recibida sin bodega.
     *
     * @return array{0: Movimiento, 1: Movimiento}
     */
    public function trasladar(
        BultoDetalle $linea,
        ?int $origenId,
        int $destinoId,
        string|int $cantidad,
        EstadoLegal $estadoLegal,
        User $usuario,
        ?string $documentoRef = null,
    ): array {
        if ($origenId === $destinoId) {
            throw new ReglaNegocioException(CodigoError::TRASLADO_MISMA_BODEGA);
        }

        $milesimas = Milesimas::de($cantidad);

        if ($milesimas <= 0) {
            throw new ReglaNegocioException(CodigoError::CANTIDAD_INVALIDA);
        }

        return [
            $this->registrar(TipoMovimiento::TRASLADO, $linea, Milesimas::texto(-$milesimas), $origenId, $estadoLegal, $usuario, $documentoRef),
            $this->registrar(TipoMovimiento::TRASLADO, $linea, Milesimas::texto($milesimas), $destinoId, $estadoLegal, $usuario, $documentoRef),
        ];
    }

    private function exigirTransaccion(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('MovimientoService debe ejecutarse dentro de DB::transaction(): el movimiento y la existencia se guardan juntos.');
        }
    }
}

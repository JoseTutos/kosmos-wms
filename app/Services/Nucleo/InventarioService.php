<?php

namespace App\Services\Nucleo;

use App\Enums\CodigoError;
use App\Enums\EstadoLegal;
use App\Exceptions\ReglaNegocioException;
use App\Models\BultoDetalle;
use App\Models\Existencia;
use App\Support\Milesimas;

/**
 * Único que escribe en existencias (tabla totalizada de la triada cliente–producto–bodega + BL, CUS-015).
 * Solo lo llama MovimientoService, dentro de la misma transacción que el movimiento.
 */
final class InventarioService
{
    /** Suma (o resta) milésimas al estado legal indicado. Bloquea la fila de existencia (FOR UPDATE). */
    public function aplicar(BultoDetalle $linea, int $bodegaId, EstadoLegal $estado, int $deltaMilesimas): Existencia
    {
        $linea->loadMissing('bl:id,cliente_id,numero_bl');

        $clave = [
            'cliente_id'  => $linea->bl->cliente_id,
            'producto_id' => $linea->producto_id,
            'bodega_id'   => $bodegaId,
            'bl_id'       => $linea->bl_id,
        ];

        // Crea la fila en 0 si no existe; ON CONFLICT DO NOTHING no aborta la transacción en PostgreSQL.
        Existencia::query()->insertOrIgnore($clave + ['created_at' => now(), 'updated_at' => now()]);

        $existencia = Existencia::query()->where($clave)->lockForUpdate()->firstOrFail();

        $columna = match ($estado) {
            EstadoLegal::CUARENTENA => 'cantidad_cuarentena',
            EstadoLegal::LEGALIZADA => 'cantidad_legalizada',
        };

        $nuevo = Milesimas::de($existencia->{$columna}) + $deltaMilesimas;
        $minimo = $columna === 'cantidad_legalizada' ? Milesimas::de($existencia->cantidad_reservada) : 0;

        if ($nuevo < $minimo) {
            throw new ReglaNegocioException(CodigoError::EXISTENCIA_INSUFICIENTE, datos: [
                'bl'          => $linea->bl->numero_bl,
                'bodega_id'   => $bodegaId,
                'estado'      => $estado->value,
                'disponible'  => Milesimas::texto(Milesimas::de($existencia->{$columna}) - $minimo),
                'solicitado'  => Milesimas::texto(-$deltaMilesimas),
            ]);
        }

        $existencia->{$columna} = Milesimas::texto($nuevo);
        $existencia->save();

        return $existencia;
    }
}

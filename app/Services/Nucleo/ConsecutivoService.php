<?php

namespace App\Services\Nucleo;

use App\Enums\CodigoError;
use App\Enums\TipoConsecutivo;
use App\Exceptions\ReglaNegocioException;
use App\Models\Cliente;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Único que entrega consecutivos (RS-07, D8). Formatos:
 *   PLT-DDMMAA-0000 · BUL-DDMMAA-0000   periodo diario (zona kosmos.zona_horaria), máx. 9999 por día
 *   {CLIENTE}-{00000}                    SKU, periodo = código corto del cliente, máx. 99999
 *   {SKU}-{0000}                         serial del aforo (RS-44, D7b), periodo = SKU, máx. 9999
 *
 * Concurrencia: INSERT … ON CONFLICT DO UPDATE … RETURNING toma el número y deja la fila
 * bloqueada hasta el fin de la transacción (igual que SELECT … FOR UPDATE), pero también cubre
 * el primer número del periodo, cuando la fila aún no existe y FOR UPDATE no tendría qué bloquear.
 * Si la transacción del llamador se revierte, el número se devuelve: no quedan huecos.
 */
final class ConsecutivoService
{
    public const MAX_DIARIO = 9999;

    public const MAX_SKU = 99999;

    public const MAX_SERIAL = 9999;

    public function siguiente(TipoConsecutivo $tipo, string $periodo, int $maximo): int
    {
        return DB::transaction(function () use ($tipo, $periodo, $maximo) {
            $fila = DB::selectOne(
                'INSERT INTO consecutivos (tipo, periodo, ultimo, updated_at) VALUES (?, ?, 1, now())
                 ON CONFLICT (tipo, periodo) DO UPDATE SET ultimo = consecutivos.ultimo + 1, updated_at = now()
                 RETURNING ultimo',
                [$tipo->value, $periodo],
                false, // conexión de escritura
            );

            $numero = (int) $fila->ultimo;

            if ($numero > $maximo) {
                throw new ReglaNegocioException(
                    CodigoError::CONSECUTIVO_AGOTADO,
                    "Se agotó el consecutivo {$tipo->value} del periodo {$periodo} (máximo {$maximo}).",
                    ['tipo' => $tipo->value, 'periodo' => $periodo],
                );
            }

            return $numero;
        });
    }

    public function codigoPallet(?DateTimeInterface $fecha = null): string
    {
        return $this->diario(TipoConsecutivo::PLT, $fecha);
    }

    public function codigoBulto(?DateTimeInterface $fecha = null): string
    {
        return $this->diario(TipoConsecutivo::BUL, $fecha);
    }

    /** SKU {CLIENTE}-{00000}. El formato lo valida el dominio (Cliente::generarSku). */
    public function sku(Cliente $cliente): string
    {
        return $cliente->generarSku($this->siguiente(TipoConsecutivo::SKU, $cliente->codigo_corto, self::MAX_SKU));
    }

    /** Serial de una unidad en el aforo: {SKU}-{0000}. */
    public function serial(string $sku): string
    {
        return sprintf('%s-%04d', $sku, $this->siguiente(TipoConsecutivo::SER, $sku, self::MAX_SERIAL));
    }

    /** DDMMAA en la zona horaria de la operación (no la del servidor). */
    public function periodoDiario(?DateTimeInterface $fecha = null): string
    {
        return CarbonImmutable::instance($fecha ?? now())
            ->setTimezone(config('kosmos.zona_horaria', 'America/Bogota'))
            ->format('dmy');
    }

    private function diario(TipoConsecutivo $tipo, ?DateTimeInterface $fecha): string
    {
        $periodo = $this->periodoDiario($fecha);

        return sprintf('%s-%s-%04d', $tipo->value, $periodo, $this->siguiente($tipo, $periodo, self::MAX_DIARIO));
    }
}

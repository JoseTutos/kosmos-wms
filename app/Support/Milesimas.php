<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Aritmética exacta de cantidades numeric(14,3): se opera en enteros (milésimas), nunca en float.
 * "12.5" ↔ 12500. Lo usan el Núcleo de Inventario y los métodos de cantidad del dominio.
 */
final class Milesimas
{
    /** "12.5" | "-2.250" | 3 → milésimas. Más de 3 decimales o un formato raro es un error de programación. */
    public static function de(string|int|float|null $valor): int
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        if (is_int($valor)) {
            return $valor * 1000;
        }

        if (is_float($valor)) {
            $valor = number_format($valor, 3, '.', '');
        }

        if (! preg_match('/^([+-])?(\d+)(?:\.(\d{0,3}))?$/', trim($valor), $m)) {
            throw new InvalidArgumentException("Cantidad no válida: «{$valor}» (máximo 3 decimales, separador punto).");
        }

        $milesimas = (int) $m[2] * 1000 + (int) str_pad($m[3] ?? '', 3, '0');

        return ($m[1] ?? '') === '-' ? -$milesimas : $milesimas;
    }

    /** 12500 → "12.500" (formato de las columnas decimal:3). */
    public static function texto(int $milesimas): string
    {
        $abs = abs($milesimas);

        return sprintf('%s%d.%03d', $milesimas < 0 ? '-' : '', intdiv($abs, 1000), $abs % 1000);
    }
}

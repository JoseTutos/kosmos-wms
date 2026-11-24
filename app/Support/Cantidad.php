<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Aritmética exacta para cantidades NUMERIC(14,3).
 *
 * Eloquent devuelve los decimales como string; sumarlos como float produce
 * errores de redondeo (0.1 + 0.2 != 0.3). Aquí se opera en milésimas enteras,
 * sin depender de la extensión bcmath.
 */
final class Cantidad
{
    private const FACTOR = 1000;

    public static function aMilesimas(string|int|float|null $valor): int
    {
        $texto = trim((string) ($valor ?? '0'));
        $texto = $texto === '' ? '0' : $texto;

        if (! preg_match('/^([+-])?(\d*)(?:\.(\d*))?$/', $texto, $m) || ($m[2] === '' && ($m[3] ?? '') === '')) {
            throw new InvalidArgumentException("Cantidad inválida: {$texto}");
        }

        $signo   = ($m[1] ?? '') === '-' ? -1 : 1;
        $entero  = (int) ($m[2] === '' ? '0' : $m[2]);
        $decimal = (int) substr(str_pad($m[3] ?? '', 3, '0'), 0, 3);

        return $signo * ($entero * self::FACTOR + $decimal);
    }

    public static function deMilesimas(int $milesimas): string
    {
        $signo = $milesimas < 0 ? '-' : '';
        $abs   = abs($milesimas);

        return sprintf('%s%d.%03d', $signo, intdiv($abs, self::FACTOR), $abs % self::FACTOR);
    }

    /** Normaliza cualquier valor a string con 3 decimales. */
    public static function de(string|int|float|null $valor): string
    {
        return self::deMilesimas(self::aMilesimas($valor));
    }

    public static function sumar(string|int|float|null $a, string|int|float|null $b): string
    {
        return self::deMilesimas(self::aMilesimas($a) + self::aMilesimas($b));
    }

    public static function restar(string|int|float|null $a, string|int|float|null $b): string
    {
        return self::deMilesimas(self::aMilesimas($a) - self::aMilesimas($b));
    }

    public static function multiplicar(string|int|float|null $a, int $factor): string
    {
        return self::deMilesimas(self::aMilesimas($a) * $factor);
    }

    /** -1 si a < b, 0 si son iguales, 1 si a > b. */
    public static function comparar(string|int|float|null $a, string|int|float|null $b): int
    {
        return self::aMilesimas($a) <=> self::aMilesimas($b);
    }

    public static function esPositiva(string|int|float|null $a): bool
    {
        return self::aMilesimas($a) > 0;
    }

    public static function esNegativa(string|int|float|null $a): bool
    {
        return self::aMilesimas($a) < 0;
    }

    public static function esCero(string|int|float|null $a): bool
    {
        return self::aMilesimas($a) === 0;
    }

    public static function absoluto(string|int|float|null $a): string
    {
        return self::deMilesimas(abs(self::aMilesimas($a)));
    }

    public static function negativo(string|int|float|null $a): string
    {
        return self::deMilesimas(-self::aMilesimas($a));
    }
}

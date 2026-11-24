<?php

namespace App\Services\Nucleo;

use App\Models\Configuracion;
use App\Support\Milesimas;
use Illuminate\Support\Facades\Cache;

/**
 * Parámetros de negocio (tabla configuraciones) con caché.
 * Orden: fila de la BD → config('kosmos.parametros') → valor por defecto del llamador.
 * Cambie los valores con establecer() (limpia la caché). Si se editan a mano en la BD: php artisan cache:clear.
 */
final class ConfiguracionService
{
    private const CACHE = 'kosmos.configuraciones';

    private const MINUTOS = 10;

    /** @return array<string, string> */
    public function todas(): array
    {
        return Cache::remember(self::CACHE, now()->addMinutes(self::MINUTOS), fn () => Configuracion::query()->pluck('valor', 'clave')->all());
    }

    public function obtener(string $clave, mixed $porDefecto = null): mixed
    {
        return $this->todas()[$clave] ?? config("kosmos.parametros.{$clave}", $porDefecto);
    }

    public function entero(string $clave): int
    {
        return (int) $this->obtener($clave);
    }

    /** Valor numérico normalizado a 3 decimales ("1.2" → "1.200"). */
    public function decimal(string $clave): string
    {
        return Milesimas::texto(Milesimas::de((string) $this->obtener($clave)));
    }

    public function establecer(string $clave, string $valor): void
    {
        Configuracion::query()->updateOrCreate(['clave' => $clave], ['valor' => $valor]);

        $this->olvidar();
    }

    public function olvidar(): void
    {
        Cache::forget(self::CACHE);
    }
}

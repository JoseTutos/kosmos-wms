<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use App\Services\Nucleo\ConfiguracionService;
use Illuminate\Database\Seeder;

/** Inserta los parámetros de config('kosmos.parametros') que falten. No pisa valores ya cambiados. */
class ConfiguracionSeeder extends Seeder
{
    public function run(ConfiguracionService $configuracion): void
    {
        foreach (config('kosmos.parametros') as $clave => $valor) {
            Configuracion::query()->insertOrIgnore(['clave' => $clave, 'valor' => (string) $valor]);
        }

        $configuracion->olvidar();
    }
}

<?php

namespace Tests\Unit\Nucleo;

use App\Models\Configuracion;
use App\Services\Nucleo\ConfiguracionService;
use App\Services\Nucleo\OcupacionService;
use App\Support\Milesimas;
use Database\Seeders\ConfiguracionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

final class OcupacionConfiguracionTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    public function test_sin_fila_en_bd_usa_el_valor_de_config(): void
    {
        $this->assertSame('1.200', app(ConfiguracionService::class)->decimal('area_pallet_m2'));
        $this->assertSame('5', Configuracion::obtener('max_intentos_login'));
        $this->assertSame('x', Configuracion::obtener('no_existe', 'x'));
    }

    public function test_el_seeder_no_pisa_valores_ya_cambiados(): void
    {
        app(ConfiguracionService::class)->establecer('tolerancia_peso', '8');

        $this->seed(ConfiguracionSeeder::class);

        $this->assertSame('8', Configuracion::obtener('tolerancia_peso'));
        $this->assertSame(count(config('kosmos.parametros')), Configuracion::count());
    }

    public function test_ocupacion_cuenta_solo_pallets_ubicados_con_el_area_configurada(): void
    {
        $config = app(ConfiguracionService::class);
        $config->establecer('area_pallet_m2', '1.50');

        $cliente = $this->crearCliente();
        $producto = $this->crearProducto($cliente);
        $bl = $this->crearBl($cliente, $producto);
        $bodega = $this->crearBodega();

        $this->palletUbicado($bl, $producto, $bodega);
        $this->palletUbicado($bl, $producto, $bodega);
        $this->crearPallet($bl); // abierto: no ocupa

        $this->assertSame(3000, Milesimas::de(app(OcupacionService::class)->recalcular($bodega)->m2_ocupados));

        $config->establecer('area_pallet_m2', '2'); // la caché se limpia al establecer

        $this->assertSame(4000, Milesimas::de(app(OcupacionService::class)->recalcular($bodega->id)->m2_ocupados));
    }
}

<?php

namespace Tests\Unit\Nucleo;

use App\Enums\CodigoError;
use App\Services\Nucleo\ConsecutivoService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

final class ConsecutivoServiceTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    private ConsecutivoService $consecutivos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->consecutivos = app(ConsecutivoService::class);
    }

    public function test_pallet_y_bulto_tienen_su_propia_secuencia_diaria(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-23 10:00', 'America/Bogota'));

        $this->assertSame('PLT-231126-0001', $this->consecutivos->codigoPallet());
        $this->assertSame('PLT-231126-0002', $this->consecutivos->codigoPallet());
        $this->assertSame('BUL-231126-0001', $this->consecutivos->codigoBulto());

        $this->travelTo(CarbonImmutable::parse('2026-11-24 08:00', 'America/Bogota'));

        $this->assertSame('PLT-241126-0001', $this->consecutivos->codigoPallet());
    }

    public function test_el_dia_se_corta_a_medianoche_de_colombia_no_del_servidor(): void
    {
        // 03:30 UTC del 24 = 22:30 del 23 en Bogotá
        $this->travelTo(CarbonImmutable::parse('2026-11-24 03:30', 'UTC'));

        $this->assertSame('BUL-231126-0001', $this->consecutivos->codigoBulto());
    }

    public function test_sku_secuencial_por_cliente_al_crear_productos(): void
    {
        $a = $this->crearCliente();
        $b = $this->crearCliente();

        $this->assertSame("{$a->codigo_corto}-00001", $this->crearProducto($a)->sku);
        $this->assertSame("{$a->codigo_corto}-00002", $this->crearProducto($a)->sku);
        $this->assertSame("{$b->codigo_corto}-00001", $this->crearProducto($b)->sku);
    }

    public function test_serial_del_aforo_sigue_al_sku(): void
    {
        $producto = $this->crearProducto($this->crearCliente());

        $this->assertSame("{$producto->sku}-0001", $this->consecutivos->serial($producto->sku));
        $this->assertSame("{$producto->sku}-0002", $this->consecutivos->serial($producto->sku));
        $this->assertMatchesRegularExpression('/^[A-Z]{3,6}-[0-9]{5}-[0-9]{4}$/', $this->consecutivos->serial($producto->sku));
    }

    public function test_consecutivo_agotado_no_consume_el_numero(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-23 10:00', 'America/Bogota'));
        DB::table('consecutivos')->insert(['tipo' => 'PLT', 'periodo' => '231126', 'ultimo' => 9999, 'updated_at' => now()]);

        $this->assertRegla(CodigoError::CONSECUTIVO_AGOTADO, fn () => $this->consecutivos->codigoPallet());

        $this->assertSame(9999, (int) DB::table('consecutivos')->where(['tipo' => 'PLT', 'periodo' => '231126'])->value('ultimo'));
    }

    public function test_si_la_operacion_falla_el_numero_se_devuelve(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-23 10:00', 'America/Bogota'));

        try {
            DB::transaction(function () {
                $this->consecutivos->codigoPallet();
                throw new \RuntimeException('falla después de tomar el número');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame('PLT-231126-0001', $this->consecutivos->codigoPallet());
    }
}

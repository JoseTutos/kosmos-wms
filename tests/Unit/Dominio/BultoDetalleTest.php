<?php

namespace Tests\Unit\Dominio;

use App\Enums\CodigoError;
use App\Enums\EstadoBulto;
use App\Models\Bl;
use App\Models\BultoDetalle;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

final class BultoDetalleTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    private Bl $bl;

    private Producto $producto;

    private BultoDetalle $linea;

    protected function setUp(): void
    {
        parent::setUp();

        $cliente = $this->crearCliente();
        $this->producto = $this->crearProducto($cliente);
        $this->bl = $this->crearBl($cliente, $this->producto);
        $this->linea = $this->crearBulto($this->bl, $this->producto, '10')->detalles()->firstOrFail();
    }

    public function test_derivados_y_ciclo_legalizar_reservar_despachar(): void
    {
        $this->assertSame('10.000', $this->linea->en_cuarentena);
        $this->assertSame('0.000', $this->linea->disponible);

        $this->linea->legalizar('4');
        $this->linea->reservar('3');
        $this->linea->despachar('2');
        $this->linea->save(); // los CHECK de la BD aceptan el resultado

        $this->linea->refresh();
        $this->assertSame('6.000', $this->linea->en_cuarentena);
        $this->assertSame('1.000', $this->linea->cantidad_reservada);
        $this->assertSame('2.000', $this->linea->cantidad_despachada);
        $this->assertSame('1.000', $this->linea->disponible);
    }

    public function test_no_se_supera_ninguna_cantidad(): void
    {
        $this->assertRegla(CodigoError::CANTIDAD_INSUFICIENTE, fn () => $this->linea->legalizar('10.001'));
        $this->assertRegla(CodigoError::CANTIDAD_INSUFICIENTE, fn () => $this->linea->reservar('1'));
        $this->assertRegla(CodigoError::CANTIDAD_INSUFICIENTE, fn () => $this->linea->despachar('1'));
        $this->assertRegla(CodigoError::CANTIDAD_INVALIDA, fn () => $this->linea->legalizar('0'));
    }

    public function test_aplicar_conteo_devuelve_la_diferencia(): void
    {
        $this->assertSame('2.000', $this->linea->aplicarConteo('12'));
        $this->assertSame('12.000', $this->linea->cantidad_recibida);
        $this->assertSame('-1.500', $this->linea->aplicarConteo('10.5'));
        $this->assertSame('0.000', $this->linea->aplicarConteo('10.5'));
    }

    public function test_conteo_en_cero_o_menor_a_lo_legalizado_se_rechaza(): void
    {
        $this->assertRegla(CodigoError::CANTIDAD_INVALIDA, fn () => $this->linea->aplicarConteo('0'));

        $this->linea->legalizar('5');
        $this->assertRegla(CodigoError::CONTEO_MENOR_A_LEGALIZADO, fn () => $this->linea->aplicarConteo('4'));
    }

    public function test_lineas_del_bulto_solo_con_productos_del_manifiesto_y_sin_repetir(): void
    {
        $bulto = $this->crearBulto($this->bl, $this->producto);
        $ajeno = $this->crearProducto($this->bl->cliente);

        $this->assertRegla(CodigoError::PRODUCTO_REPETIDO_EN_BULTO, fn () => $bulto->agregarLinea($this->producto, '1'));
        $this->assertRegla(CodigoError::PRODUCTO_NO_EN_BL, fn () => $bulto->agregarLinea($ajeno, '1'));
        $this->assertRegla(CodigoError::CANTIDAD_INVALIDA, fn () => $this->crearBulto($this->bl)->agregarLinea($this->producto, '0'));
    }

    public function test_anular_saca_el_bulto_de_un_pallet_abierto_pero_no_de_uno_cerrado(): void
    {
        $pallet = $this->crearPallet($this->bl);
        $bulto = $this->crearBulto($this->bl, $this->producto);
        $pallet->agregarBulto($bulto);
        $bulto->save();

        $bulto->anular();
        $this->assertSame(EstadoBulto::ANULADO, $bulto->estado);
        $this->assertNull($bulto->pallet_id);
        $this->assertRegla(CodigoError::BULTO_ANULADO, fn () => $bulto->anular());

        $otro = $this->crearBulto($this->bl, $this->producto);
        $pallet->agregarBulto($otro);
        $otro->save();
        $pallet->cerrar();
        $pallet->save();

        $this->assertRegla(CodigoError::PALLET_CERRADO, fn () => $otro->refresh()->anular());
    }
}

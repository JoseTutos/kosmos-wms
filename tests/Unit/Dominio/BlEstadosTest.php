<?php

namespace Tests\Unit\Dominio;

use App\Enums\CodigoError;
use App\Enums\EstadoAforo;
use App\Enums\EstadoBl;
use App\Models\Aforo;
use App\Models\AforoDetalle;
use App\Models\Bl;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

final class BlEstadosTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    private Bl $bl;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $cliente = $this->crearCliente();
        $this->producto = $this->crearProducto($cliente);
        $this->bl = $this->crearBl($cliente, $this->producto);
    }

    public function test_nace_programado_y_editable(): void
    {
        $this->assertSame(EstadoBl::PROGRAMADO, $this->bl->estado);
        $this->assertTrue($this->bl->esEditable());
    }

    public function test_transiciones_invalidas(): void
    {
        $this->assertRegla(CodigoError::BL_NO_EN_RECEPCION, fn () => $this->bl->finalizarRecepcion('ok'));

        $this->bl->iniciarRecepcion();
        $this->assertFalse($this->bl->esEditable());
        $this->assertRegla(CodigoError::BL_ESTADO_INVALIDO, fn () => $this->bl->iniciarRecepcion());
    }

    public function test_diferencias_exigen_observacion(): void
    {
        $this->bl->iniciarRecepcion();
        $this->bl->registrarDiscrepancia(10, 9);

        $this->assertRegla(CodigoError::OBSERVACION_REQUERIDA, fn () => $this->bl->finalizarRecepcion('   '));

        $this->bl->finalizarRecepcion('Falta un bulto; el transportador lo reporta en el acta.');
        $this->bl->save(); // CHECK discrepancia ⇒ observación

        $this->assertSame(EstadoBl::RECIBIDO, $this->bl->refresh()->estado);
        $this->assertSame(-1, $this->bl->discrepancia_bultos);
    }

    public function test_novedades_del_service_tambien_exigen_observacion(): void
    {
        $this->bl->iniciarRecepcion();

        $this->assertRegla(CodigoError::OBSERVACION_REQUERIDA, fn () => $this->bl->finalizarRecepcion(null, hayNovedades: true));

        $this->bl->finalizarRecepcion(null); // sin diferencias no hace falta
        $this->assertSame(EstadoBl::RECIBIDO, $this->bl->estado);
    }

    public function test_saldo_pasa_a_despacho_parcial_y_luego_a_cerrado(): void
    {
        $linea = $this->crearBulto($this->bl, $this->producto, '10')->detalles()->firstOrFail();
        $this->bl->iniciarRecepcion();
        $this->bl->finalizarRecepcion(null);
        $this->bl->save();

        $linea->legalizar('10');
        $linea->reservar('10');
        $linea->despachar('4');
        $linea->save();

        $this->bl->actualizarSaldo();
        $this->assertSame(EstadoBl::DESPACHADO_PARCIAL, $this->bl->estado);

        $linea->despachar('6');
        $linea->save();

        $this->bl->actualizarSaldo();
        $this->assertSame(EstadoBl::CERRADO, $this->bl->estado);
    }

    public function test_aforo_solo_cierra_con_todo_contado(): void
    {
        $linea = $this->crearBulto($this->bl, $this->producto, '10')->detalles()->firstOrFail();
        $aforo = Aforo::create(['bl_id' => $this->bl->id])->refresh();
        $detalle = AforoDetalle::create(['aforo_id' => $aforo->id, 'bulto_detalle_id' => $linea->id, 'cantidad_manifiesto' => '10']);

        $this->assertRegla(CodigoError::AFORO_INCOMPLETO, fn () => $aforo->cerrar());

        $detalle->registrarConteo('9');
        $detalle->save();
        $this->assertSame('-1.000', $detalle->diferencia);

        $aforo->cerrar();
        $this->assertSame(EstadoAforo::CERRADO, $aforo->estado);
        $this->assertRegla(CodigoError::AFORO_NO_EN_PROCESO, fn () => $aforo->cancelar());
    }
}

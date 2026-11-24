<?php

namespace Tests\Unit\Dominio;

use App\Enums\CodigoError;
use App\Enums\EstadoPallet;
use App\Models\Bl;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

final class PalletTest extends TestCase
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

    public function test_armar_cerrar_y_ubicar(): void
    {
        $bodega = $this->crearBodega();
        $pallet = $this->palletUbicado($this->bl, $this->producto, $bodega)->refresh();

        $this->assertSame(EstadoPallet::UBICADO, $pallet->estado);
        $this->assertSame($bodega->id, $pallet->bodega_id);
        $this->assertSame(1, $pallet->bultos()->count());
    }

    public function test_no_se_agrega_a_un_pallet_cerrado(): void
    {
        $pallet = $this->crearPallet($this->bl);
        $primero = $this->crearBulto($this->bl, $this->producto);
        $pallet->agregarBulto($primero);
        $primero->save();
        $pallet->cerrar();

        $this->assertRegla(CodigoError::PALLET_CERRADO, fn () => $pallet->agregarBulto($this->crearBulto($this->bl, $this->producto)));
    }

    public function test_no_se_mezclan_bultos_de_otro_bl(): void
    {
        $otroBl = $this->crearBl($this->crearCliente());

        $this->assertRegla(CodigoError::BULTO_OTRO_BL, fn () => $this->crearPallet($this->bl)->agregarBulto($this->crearBulto($otroBl)));
    }

    public function test_un_bulto_no_esta_en_dos_pallets_ni_se_retira_de_uno_ajeno(): void
    {
        $uno = $this->crearPallet($this->bl);
        $dos = $this->crearPallet($this->bl);
        $bulto = $this->crearBulto($this->bl, $this->producto);
        $uno->agregarBulto($bulto);

        $this->assertRegla(CodigoError::BULTO_EN_OTRO_PALLET, fn () => $dos->agregarBulto($bulto));
        $this->assertRegla(CodigoError::BULTO_NO_EN_PALLET, fn () => $dos->retirarBulto($bulto));
    }

    public function test_no_se_cierra_vacio_ni_se_ubica_abierto(): void
    {
        $pallet = $this->crearPallet($this->bl);

        $this->assertRegla(CodigoError::PALLET_VACIO, fn () => $pallet->cerrar());
        $this->assertRegla(CodigoError::PALLET_ESTADO_INVALIDO, fn () => $pallet->ubicar($this->crearBodega()));
    }

    public function test_reglas_de_destino(): void
    {
        $conUbicaciones = $this->crearBodega(true);
        $otraBodega = $this->crearBodega(true);
        $simple = $this->crearBodega();

        $pallet = $this->crearPallet($this->bl);
        $bulto = $this->crearBulto($this->bl, $this->producto);
        $pallet->agregarBulto($bulto);
        $bulto->save();
        $pallet->cerrar();

        $this->assertRegla(CodigoError::UBICACION_REQUERIDA, fn () => $pallet->ubicar($conUbicaciones));
        $this->assertRegla(CodigoError::UBICACION_OTRA_BODEGA, fn () => $pallet->ubicar($conUbicaciones, $this->crearUbicacion($otraBodega)));
        $this->assertRegla(CodigoError::UBICACION_NO_PERMITIDA, fn () => $pallet->ubicar($simple, $this->crearUbicacion($conUbicaciones)));

        $simple->inactivar();
        $this->assertRegla(CodigoError::BODEGA_INACTIVA, fn () => $pallet->ubicar($simple));

        $this->assertNull($pallet->ubicar($conUbicaciones, $this->crearUbicacion($conUbicaciones))); // primera ubicación
        $this->assertSame($conUbicaciones->id, $pallet->ubicar($otraBodega, $this->crearUbicacion($otraBodega))); // traslado
    }

    public function test_reabrir_solo_desde_cerrado(): void
    {
        $bodega = $this->crearBodega();
        $pallet = $this->palletUbicado($this->bl, $this->producto, $bodega);

        $this->assertRegla(CodigoError::PALLET_ESTADO_INVALIDO, fn () => $pallet->reabrir());
    }
}

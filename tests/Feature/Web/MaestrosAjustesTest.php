<?php

namespace Tests\Feature\Web;

use App\Enums\CodigoError;
use App\Enums\EstadoActiva;
use App\Enums\Rol;
use App\Models\Cliente;
use App\Rules\CodigoMaestro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

/** Paso 5 · ajustes de Maestros con datos reales del Módulo 2. */
final class MaestrosAjustesTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    public function test_codigos_de_bodega_y_ubicacion_no_chocan_con_el_escaner(): void
    {
        $valida = fn (string $codigo) => Validator::make(['codigo' => $codigo], ['codigo' => [new CodigoMaestro]])->passes();

        $this->assertTrue($valida('BOD-01'));
        $this->assertTrue($valida('A-01-01'));
        $this->assertFalse($valida('PLT-01'));
        $this->assertFalse($valida('bul-norte'));
        $this->assertFalse($valida('ACME-00001'));
        $this->assertFalse($valida('ACME-00001-0001'));
        $this->assertFalse($valida('BOD/01'));
    }

    public function test_bodega_y_ubicacion_con_mercancia_no_se_inactivan(): void
    {
        $cliente = $this->crearCliente();
        $producto = $this->crearProducto($cliente);
        $bl = $this->crearBl($cliente, $producto);
        $bodega = $this->crearBodega(true);
        $ubicacion = $this->crearUbicacion($bodega);

        $pallet = $this->crearPallet($bl);
        $bulto = $this->crearBulto($bl, $producto);
        $pallet->agregarBulto($bulto);
        $bulto->save();
        $pallet->cerrar();
        $pallet->ubicar($bodega, $ubicacion);
        $pallet->save();

        $this->assertRegla(CodigoError::BODEGA_CON_MERCANCIA, fn () => $bodega->inactivar());
        $this->assertRegla(CodigoError::UBICACION_CON_MERCANCIA, fn () => $ubicacion->inactivar());

        $vacia = $this->crearBodega();
        $vacia->inactivar();
        $this->assertSame(EstadoActiva::INACTIVA, $vacia->estado);
    }

    public function test_tiene_inventario_del_cliente(): void
    {
        $conBl = $this->crearCliente();
        $producto = $this->crearProducto($conBl);
        $bl = $this->crearBl($conBl, $producto);
        $bl->iniciarRecepcion();
        $bl->save();
        $sinNada = $this->crearCliente();

        $marcas = Cliente::query()->conTieneInventario()->pluck('tiene_inventario', 'id');

        $this->assertTrue($marcas[$conBl->id]);
        $this->assertFalse($marcas[$sinNada->id]);
    }

    public function test_primera_etiqueta_de_bodega_sin_motivo_solo_recien_creada(): void
    {
        $admin = $this->crearUsuario(Rol::ADMIN_MAESTROS);
        $bodega = $this->crearBodega();

        $this->actingAs($admin)
            ->post(route('etiquetas.imprimir'), ['tipo' => 'bodega', 'codigo' => $bodega->codigo])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('activity_log', ['log_name' => 'etiquetas', 'description' => 'etiqueta impresa']);

        $this->travel(11)->minutes();

        $this->actingAs($admin)
            ->from(route('etiquetas.index'))
            ->post(route('etiquetas.imprimir'), ['tipo' => 'bodega', 'codigo' => $bodega->codigo])
            ->assertRedirect(route('etiquetas.index'))
            ->assertSessionHasErrors('codigo');
    }
}

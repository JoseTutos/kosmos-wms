<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EstadoActiva;
use App\Enums\Rol;
use App\Models\Bl;
use App\Models\Bodega;
use App\Models\Existencia;
use App\Models\Movimiento;
use App\Models\Pallet;
use App\Models\Producto;
use App\Models\User;
use App\Support\Milesimas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** CUS-008 Ubicar y trasladar (RS-15, RS-17, RS-38, U1–U7). */
final class UbicarTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase, UsaApi;

    private const URI = '/api/v1/almacenamiento/ubicar';

    private User $operario;

    private Producto $producto;

    private Bl $bl;

    private Bodega $bodega;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operario = $this->crearUsuario(Rol::OPERARIO);
        $cliente = $this->crearCliente();
        $this->producto = $this->crearProducto($cliente);
        $this->bl = $this->crearBl($cliente, $this->producto);
        $this->bl->iniciarRecepcion();
        $this->bl->save();
        $this->bodega = $this->crearBodega();
    }

    private function palletCerrado(): Pallet
    {
        $pallet = $this->crearPallet($this->bl);
        $bulto = $this->crearBulto($this->bl, $this->producto);
        $pallet->agregarBulto($bulto);
        $bulto->save();
        $pallet->cerrar();
        $pallet->save();

        return $pallet;
    }

    private function ubicar(Pallet $pallet, Bodega $bodega, ?string $ubicacion = null, ?string $escaneo = null)
    {
        return $this->apiPost($this->operario, self::URI, array_filter([
            'pallet'    => $pallet->codigo,
            'bodega'    => $bodega->codigo,
            'ubicacion' => $ubicacion,
            'escaneo'   => $escaneo ?? ($ubicacion ? "{$bodega->codigo}/{$ubicacion}" : $bodega->codigo),
        ]));
    }

    public function test_ubica_un_pallet_cerrado_antes_de_finalizar_el_bl(): void
    {
        $pallet = $this->palletCerrado();

        $this->ubicar($pallet, $this->bodega)
            ->assertOk()
            ->assertJsonPath('data.operacion', 'UBICACION')
            ->assertJsonPath('data.pallet.estado', 'UBICADO')
            ->assertJsonPath('data.pallet.bodega', $this->bodega->codigo)
            ->assertJsonPath('data.bodega.m2_ocupados', '1.20')
            ->assertJsonPath('data.bodega_origen', null)
            ->assertJsonPath('data.alerta_sobrecupo', false);

        $this->assertSame('UBICADO', $pallet->bultos()->first()->estado->value);
        $this->assertSame(0, Movimiento::count());   // N11: con el BL en recepción el inventario lo registra finalizar
    }

    public function test_el_escaneo_del_destino_debe_coincidir(): void
    {
        $pallet = $this->palletCerrado();
        $otra = $this->crearBodega();

        $this->ubicar($pallet, $this->bodega, escaneo: $otra->codigo)
            ->assertStatus(422)
            ->assertJsonPath('codigo', 'ESCANEO_NO_COINCIDE')
            ->assertJsonPath('datos.esperado', $this->bodega->codigo);

        $this->assertSame('CERRADO', $pallet->refresh()->estado->value);
    }

    public function test_bodega_con_ubicaciones_exige_ubicacion(): void
    {
        $pallet = $this->palletCerrado();
        $bodega = $this->crearBodega(true);
        $ubicacion = $this->crearUbicacion($bodega);

        $this->ubicar($pallet, $bodega)->assertStatus(422)->assertJsonPath('codigo', 'UBICACION_REQUERIDA');

        $this->ubicar($pallet, $bodega, $ubicacion->codigo, strtolower("{$bodega->codigo}/{$ubicacion->codigo}"))
            ->assertOk()
            ->assertJsonPath('data.pallet.ubicacion', $ubicacion->codigo);
    }

    public function test_reglas_de_estado(): void
    {
        $abierto = $this->crearPallet($this->bl);
        $this->ubicar($abierto, $this->bodega)->assertStatus(422)->assertJsonPath('codigo', 'PALLET_ESTADO_INVALIDO');

        $pallet = $this->palletCerrado();
        $inactiva = $this->crearBodega();
        $inactiva->estado = EstadoActiva::INACTIVA;
        $inactiva->save();
        $this->ubicar($pallet, $inactiva)->assertStatus(422)->assertJsonPath('codigo', 'BODEGA_INACTIVA');

        $bulto = $pallet->bultos()->first();
        $bulto->iniciarAforo();
        $bulto->save();
        $this->ubicar($pallet, $this->bodega)->assertStatus(422)->assertJsonPath('codigo', 'PALLET_EN_AFORO');

        $this->apiPost($this->operario, self::URI, ['pallet' => 'PLT-010126-9999', 'bodega' => $this->bodega->codigo, 'escaneo' => $this->bodega->codigo])
            ->assertStatus(422)->assertJsonPath('codigo', 'CODIGO_NO_ENCONTRADO');
    }

    public function test_traslado_con_bl_recibido_mueve_inventario_y_recalcula_ambas_bodegas(): void
    {
        $pallet = $this->palletCerrado();
        $this->ubicar($pallet, $this->bodega)->assertOk();

        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/finalizar", ['observacion' => 'Llegó un solo bulto.'])->assertOk();
        $this->assertSame(10000, Milesimas::de(Existencia::where('bodega_id', $this->bodega->id)->value('cantidad_cuarentena')));

        $destino = $this->crearBodega();

        $this->ubicar($pallet, $destino)
            ->assertOk()
            ->assertJsonPath('data.operacion', 'TRASLADO')
            ->assertJsonPath('data.bodega.m2_ocupados', '1.20')
            ->assertJsonPath('data.bodega_origen.codigo', $this->bodega->codigo)
            ->assertJsonPath('data.bodega_origen.m2_ocupados', '0.00');

        $this->assertSame(2, Movimiento::where('tipo', 'TRASLADO')->count());
        $this->assertSame(0, Milesimas::de(Existencia::where('bodega_id', $this->bodega->id)->value('cantidad_cuarentena')));
        $this->assertSame(10000, Milesimas::de(Existencia::where('bodega_id', $destino->id)->value('cantidad_cuarentena')));
    }

    public function test_sobrecupo_solo_advierte(): void
    {
        $pequena = Bodega::create(['codigo' => 'MINI-1', 'nombre' => 'Bodega pequeña', 'tamano_m2' => '1.00', 'maneja_ubicaciones' => false]);

        $this->ubicar($this->palletCerrado(), $pequena)
            ->assertOk()
            ->assertJsonPath('data.alerta_sobrecupo', true);
    }

    public function test_destinos_lista_solo_bodegas_activas(): void
    {
        $inactiva = $this->crearBodega();
        $inactiva->estado = EstadoActiva::INACTIVA;
        $inactiva->save();

        $codigos = $this->apiGet($this->operario, '/api/v1/almacenamiento/destinos')->assertOk()->json('data.*.codigo');

        $this->assertContains($this->bodega->codigo, $codigos);
        $this->assertNotContains($inactiva->codigo, $codigos);
    }
}

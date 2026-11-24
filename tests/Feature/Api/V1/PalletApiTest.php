<?php

namespace Tests\Feature\Api\V1;

use App\Enums\Rol;
use App\Models\Bl;
use App\Models\Pallet;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** CUS-006 armar pallets por escaneo (P1 a P4, D7). */
final class PalletApiTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase, UsaApi;

    private User $operario;

    private Producto $producto;

    private Bl $bl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operario = $this->crearUsuario(Rol::OPERARIO);
        $cliente = $this->crearCliente();
        $this->producto = $this->crearProducto($cliente);
        $this->bl = $this->crearBl($cliente, $this->producto);
        $this->bl->iniciarRecepcion();
        $this->bl->save();
    }

    private function crear(): string
    {
        return $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/pallets")
            ->assertCreated()
            ->assertJsonPath('data.pallet.estado', 'ABIERTO')
            ->assertJsonPath('data.etiqueta.formato', 'ZPL')
            ->json('data.pallet.codigo');
    }

    private function bulto(?Bl $bl = null): string
    {
        return $this->crearBulto($bl ?? $this->bl, $bl ? null : $this->producto)->codigo;
    }

    public function test_armar_cerrar_y_reabrir(): void
    {
        $pallet = $this->crear();
        $this->assertMatchesRegularExpression('/^PLT-\d{6}-\d{4}$/', $pallet);

        $b1 = $this->bulto();
        $b2 = $this->bulto();

        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/bultos", ['bulto' => $b1])->assertOk();
        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/bultos", ['bulto' => strtolower($b2)])   // el escáner puede leer minúsculas
            ->assertOk()
            ->assertJsonPath('data.total_bultos', 2);

        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/retirar", ['bulto' => $b2])
            ->assertOk()->assertJsonPath('data.bultos', [$b1]);

        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/cerrar")->assertOk()->assertJsonPath('data.estado', 'CERRADO');

        // P3: cerrado no admite ni retirar ni agregar
        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/bultos", ['bulto' => $b2])
            ->assertStatus(422)->assertJsonPath('codigo', 'PALLET_CERRADO');

        // P2: se reabre mientras no esté ubicado
        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/reabrir")->assertOk()->assertJsonPath('data.estado', 'ABIERTO');
    }

    public function test_reglas_del_escaneo(): void
    {
        $pallet = $this->crear();
        $otro = $this->crear();
        $bulto = $this->bulto();

        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/bultos", ['bulto' => 'BUL-010101-9999'])
            ->assertStatus(422)->assertJsonPath('codigo', 'CODIGO_NO_ENCONTRADO');

        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/bultos", ['bulto' => $bulto])->assertOk();
        $this->apiPost($this->operario, "/api/v1/pallets/{$otro}/bultos", ['bulto' => $bulto])
            ->assertStatus(422)->assertJsonPath('codigo', 'BULTO_EN_OTRO_PALLET');

        // RS-30: no se mezclan BL
        $otroBl = $this->crearBl($this->bl->cliente);
        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/bultos", ['bulto' => $this->bulto($otroBl)])
            ->assertStatus(422)->assertJsonPath('codigo', 'BULTO_OTRO_BL');

        $this->apiPost($this->operario, "/api/v1/pallets/{$otro}/cerrar")
            ->assertStatus(422)->assertJsonPath('codigo', 'PALLET_VACIO');
    }

    public function test_eliminar_solo_vacio(): void
    {
        $vacio = $this->crear();
        $conBultos = $this->crear();
        $this->apiPost($this->operario, "/api/v1/pallets/{$conBultos}/bultos", ['bulto' => $this->bulto()])->assertOk();

        $this->apiDelete($this->operario, "/api/v1/pallets/{$conBultos}")
            ->assertStatus(422)->assertJsonPath('codigo', 'PALLET_ESTADO_INVALIDO');
        $this->apiDelete($this->operario, "/api/v1/pallets/{$vacio}")->assertNoContent();

        $this->assertNull(Pallet::where('codigo', $vacio)->first());
    }

    public function test_recibido_el_bl_no_se_tocan_sus_pallets(): void
    {
        $pallet = $this->crear();
        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/bultos", ['bulto' => $this->bulto()])->assertOk();

        $this->bl->refresh();
        $this->bl->finalizarRecepcion('Prueba', true);
        $this->bl->save();

        $this->apiPost($this->operario, "/api/v1/pallets/{$pallet}/retirar", ['bulto' => 'X'])
            ->assertStatus(422)->assertJsonPath('codigo', 'BL_NO_EN_RECEPCION');
        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/pallets")
            ->assertStatus(422)->assertJsonPath('codigo', 'BL_NO_EN_RECEPCION');
    }

    public function test_pallet_inexistente_es_404(): void
    {
        $this->apiGet($this->operario, '/api/v1/pallets/PLT-010101-9999')
            ->assertNotFound()->assertJsonPath('codigo', 'NO_ENCONTRADO');
    }
}

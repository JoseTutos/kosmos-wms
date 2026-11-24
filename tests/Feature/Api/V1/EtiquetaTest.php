<?php

namespace Tests\Feature\Api\V1;

use App\Enums\Rol;
use App\Models\Bl;
use App\Models\Bulto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** CUS-007 etiquetas: ZPL en la app (E1, E4, E5, E7) y PDF en la web (E6). */
final class EtiquetaTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase, UsaApi;

    private User $operario;

    private Bl $bl;

    private Bulto $bulto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operario = $this->crearUsuario(Rol::OPERARIO);
        $cliente = $this->crearCliente();
        $producto = $this->crearProducto($cliente);
        $this->bl = $this->crearBl($cliente, $producto);
        $this->bulto = $this->crearBulto($this->bl, $producto, '70');
    }

    private function reimprimir(array $datos)
    {
        return $this->apiPost($this->operario, '/api/v1/etiquetas/reimprimir', $datos + ['tipo' => 'bulto', 'codigo' => $this->bulto->codigo]);
    }

    public function test_zpl_4x2_a_203_dpi_con_codigo_y_datos_del_bulto(): void
    {
        $zpl = $this->reimprimir(['motivo' => 'DANADA'])->assertOk()->json('data.contenido');

        $this->assertStringStartsWith('^XA', $zpl);
        $this->assertStringContainsString('^PW812', $zpl);                       // 4 in × 203 dpi
        $this->assertStringContainsString('^LL406', $zpl);                       // 2 in × 203 dpi
        $this->assertStringContainsString('^BCN', $zpl);                         // Code 128
        $this->assertStringContainsString('^FD'.$this->bulto->codigo.'^FS', $zpl);
        $this->assertStringContainsString($this->bl->numero_bl, $zpl);
        $this->assertStringContainsString('× 70', $zpl);
        $this->assertStringEndsWith("^XZ\n", $zpl);
    }

    public function test_reimpresion_queda_en_la_bitacora(): void
    {
        $this->reimprimir(['motivo' => 'OTRO', 'detalle' => 'Se mojó en el patio'])->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'etiquetas', 'description' => 'etiqueta reimpresa',
            'subject_type' => Bulto::class, 'subject_id' => $this->bulto->id, 'causer_id' => $this->operario->id,
        ]);
    }

    public function test_motivo_otro_exige_detalle(): void
    {
        $this->reimprimir(['motivo' => 'OTRO'])->assertStatus(422)->assertJsonPath('codigo', 'VALIDACION');
    }

    public function test_sin_motivo_solo_en_los_primeros_minutos(): void
    {
        $this->reimprimir([])->assertOk();                                       // E7: la impresora falló en el alta

        $this->travel(11)->minutes();

        $this->reimprimir([])->assertStatus(422)->assertJsonPath('codigo', 'MOTIVO_REIMPRESION_REQUERIDO');
        $this->reimprimir(['motivo' => 'NO_IMPRIMIO'])->assertOk();
    }

    public function test_la_app_no_reimprime_bodegas_y_avisa_codigos_inexistentes(): void
    {
        $this->reimprimir(['tipo' => 'bodega', 'codigo' => 'BOD-01', 'motivo' => 'DANADA'])
            ->assertStatus(422)->assertJsonPath('codigo', 'VALIDACION');

        $this->reimprimir(['codigo' => 'BUL-010101-9999', 'motivo' => 'DANADA'])
            ->assertStatus(422)->assertJsonPath('codigo', 'CODIGO_NO_ENCONTRADO');
    }

    // ------------------------------------------------------------ web (PDF)

    public function test_el_supervisor_reimprime_en_pdf(): void
    {
        $this->actingAs($this->crearUsuario(Rol::SUPERVISOR))
            ->post(route('etiquetas.reimprimir'), ['tipo' => 'bulto', 'codigo' => $this->bulto->codigo, 'motivo' => 'ILEGIBLE'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_maestros_reimprime_ubicaciones_pero_no_bultos(): void
    {
        $bodega = $this->crearBodega(true);
        $ubicacion = $this->crearUbicacion($bodega);
        $admin = $this->crearUsuario(Rol::ADMIN_MAESTROS);

        $this->actingAs($admin)
            ->post(route('etiquetas.reimprimir'), ['tipo' => 'ubicacion', 'codigo' => "{$bodega->codigo}/{$ubicacion->codigo}", 'motivo' => 'DANADA'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($admin)
            ->from(route('etiquetas.index'))
            ->post(route('etiquetas.reimprimir'), ['tipo' => 'bulto', 'codigo' => $this->bulto->codigo, 'motivo' => 'DANADA'])
            ->assertRedirect(route('etiquetas.index'))
            ->assertSessionHasErrors('tipo');
    }

    public function test_en_la_web_el_motivo_siempre_es_obligatorio(): void
    {
        $this->actingAs($this->crearUsuario(Rol::SUPERVISOR))
            ->post(route('etiquetas.reimprimir'), ['tipo' => 'bulto', 'codigo' => $this->bulto->codigo])
            ->assertSessionHasErrors('motivo');
    }
}

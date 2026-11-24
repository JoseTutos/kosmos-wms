<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EstadoBl;
use App\Enums\EstadoBulto;
use App\Enums\EstadoPallet;
use App\Enums\Rol;
use App\Models\Bl;
use App\Models\Bulto;
use App\Models\Existencia;
use App\Models\Movimiento;
use App\Models\Pallet;
use App\Models\Producto;
use App\Models\User;
use App\Services\Nucleo\ConfiguracionService;
use App\Support\Milesimas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** CUS-006 Recibir mercancía (API de la app). */
final class RecepcionTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase, UsaApi;

    private User $operario;

    private Producto $a;

    private Producto $b;

    private Bl $bl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operario = $this->crearUsuario(Rol::OPERARIO);
        $cliente = $this->crearCliente();
        $this->a = $this->crearProducto($cliente);
        $this->b = $this->crearProducto($cliente);
        $this->b->forceFill(['unidad_medida' => 'KG'])->save();   // R5: KG admite decimales
        // crearBl: cada producto con 10 bultos, 100 unidades y 500 kg esperados
        $this->bl = $this->crearBl($cliente, $this->a, $this->b);
    }

    private function iniciar(): void
    {
        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/iniciar")->assertOk();
    }

    /** @param list<array{0: Producto, 1: string}> $lineas */
    private function bulto(array $lineas, string $peso = '50', int $repetir = 1, ?string $pallet = null)
    {
        return $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/bultos", [
            'peso_bruto_kg' => $peso,
            'lineas'        => array_map(fn ($l) => ['producto_id' => $l[0]->id, 'cantidad' => $l[1]], $lineas),
            'repetir'       => $repetir,
            'pallet'        => $pallet,
        ]);
    }

    private function palletNuevo(): string
    {
        return $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/pallets")->assertCreated()->json('data.pallet.codigo');
    }

    // ------------------------------------------------------------ iniciar

    public function test_listado_muestra_los_bl_por_recibir(): void
    {
        $this->apiGet($this->operario, '/api/v1/recepciones')
            ->assertOk()
            ->assertJsonPath('data.0.numero_bl', $this->bl->numero_bl)
            ->assertJsonPath('data.0.bultos_esperados', 20);
    }

    public function test_iniciar_fija_la_fecha_real_y_un_segundo_operario_se_une(): void
    {
        $this->bl->forceFill(['fecha_ingreso' => '2026-01-01'])->save();

        $this->iniciar();
        $this->apiPost($this->crearUsuario(Rol::OPERARIO), "/api/v1/recepciones/{$this->bl->id}/iniciar")
            ->assertOk()
            ->assertJsonPath('data.bl.estado', 'EN_RECEPCION');

        $this->bl->refresh();
        $this->assertSame(EstadoBl::EN_RECEPCION, $this->bl->estado);
        $this->assertSame(now(config('kosmos.zona_horaria'))->toDateString(), $this->bl->fecha_ingreso->toDateString()); // B3
    }

    public function test_no_inicia_con_cliente_o_producto_inactivo(): void
    {
        $this->a->inactivar();
        $this->a->save();

        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/iniciar")
            ->assertStatus(422)->assertJsonPath('codigo', 'PRODUCTO_INACTIVO');

        $this->bl->cliente->inactivar();
        $this->bl->cliente->save();

        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/iniciar")
            ->assertStatus(422)->assertJsonPath('codigo', 'CLIENTE_INACTIVO');
    }

    // ------------------------------------------------------------ bultos

    public function test_sin_iniciar_no_se_registran_bultos(): void
    {
        $this->bulto([[$this->a, '10']])->assertStatus(422)->assertJsonPath('codigo', 'BL_NO_EN_RECEPCION');
    }

    public function test_bulto_mixto_repetido_devuelve_etiquetas_y_totales(): void
    {
        $this->iniciar();

        $respuesta = $this->bulto([[$this->a, '10'], [$this->b, '2,5']], peso: '48,5', repetir: 3)->assertCreated();

        $respuesta->assertJsonCount(3, 'data.bultos')
            ->assertJsonCount(3, 'data.etiquetas')
            ->assertJsonPath('data.bultos.0.peso_bruto_kg', '48.500')
            ->assertJsonPath('data.bultos.0.lineas.1.cantidad', '2.500')
            ->assertJsonPath('data.etiquetas.0.formato', 'ZPL')
            ->assertJsonPath('data.recepcion.bultos.recibidos', 3)
            ->assertJsonPath('data.recepcion.bultos.esperados', 20)
            ->assertJsonPath('data.recepcion.bultos.sin_pallet', 3)
            ->assertJsonPath('data.recepcion.peso_kg.recibido', '145.500')
            ->assertJsonPath('data.recepcion.productos.0.recibidas', '30.000');

        $codigos = $respuesta->json('data.bultos.*.codigo');
        $this->assertCount(3, array_unique($codigos));
        $this->assertStringContainsString('^BCN', $respuesta->json('data.etiquetas.0.contenido'));
        $this->assertStringContainsString($codigos[0], $respuesta->json('data.etiquetas.0.contenido'));
    }

    public function test_reglas_de_las_lineas(): void
    {
        $this->iniciar();
        $ajeno = $this->crearProducto($this->bl->cliente);   // del cliente, pero no del manifiesto

        $this->bulto([[$ajeno, '1']])->assertStatus(422)->assertJsonPath('codigo', 'PRODUCTO_NO_EN_BL');
        $this->bulto([[$this->a, '1,5']])->assertStatus(422)->assertJsonPath('codigo', 'CANTIDAD_INVALIDA');   // R5: UND entero
        $this->bulto([[$this->a, '1'], [$this->a, '2']])->assertStatus(422)->assertJsonPath('codigo', 'VALIDACION'); // un renglón por producto
        $this->bulto([[$this->a, '1']], peso: '0')->assertStatus(422)->assertJsonPath('codigo', 'VALIDACION');    // R4

        $maximo = app(ConfiguracionService::class)->entero('repetir_max');
        $this->bulto([[$this->a, '1']], repetir: $maximo + 1)->assertStatus(422)->assertJsonPath('codigo', 'VALIDACION');

        $this->assertSame(0, Bulto::count());
    }

    public function test_bulto_directo_al_pallet_y_anulacion(): void
    {
        $this->iniciar();
        $pallet = $this->palletNuevo();

        $codigo = $this->bulto([[$this->a, '10']], pallet: $pallet)->assertCreated()
            ->assertJsonPath('data.bultos.0.pallet', $pallet)
            ->json('data.bultos.0.codigo');

        // R9: anular lo saca del pallet abierto y deja de contar
        $this->apiPost($this->operario, "/api/v1/bultos/{$codigo}/anular")
            ->assertOk()
            ->assertJsonPath('data.bulto.estado', 'ANULADO')
            ->assertJsonPath('data.bulto.pallet', null)
            ->assertJsonPath('data.recepcion.bultos.recibidos', 0);

        $this->apiPost($this->operario, "/api/v1/bultos/{$codigo}/anular")->assertStatus(422)->assertJsonPath('codigo', 'BULTO_ANULADO');
    }

    // ------------------------------------------------------------ finalizar

    public function test_no_finaliza_con_bultos_sin_pallet(): void
    {
        $this->iniciar();
        $this->bulto([[$this->a, '10']]);

        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/finalizar")
            ->assertStatus(422)
            ->assertJsonPath('codigo', 'BULTOS_SIN_PALLET')
            ->assertJsonPath('datos.total', 1);
    }

    public function test_diferencias_exigen_observacion(): void
    {
        $this->iniciar();
        $pallet = $this->palletNuevo();
        $this->bulto([[$this->a, '10']], pallet: $pallet);  // 1 de 20 bultos

        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/finalizar")
            ->assertStatus(422)->assertJsonPath('codigo', 'OBSERVACION_REQUERIDA');

        $this->assertSame(EstadoBl::EN_RECEPCION, $this->bl->refresh()->estado);
    }

    public function test_finalizar_cierra_pallets_y_alimenta_el_inventario(): void
    {
        $this->iniciar();
        $abierto = $this->palletNuevo();
        $vacio = $this->palletNuevo();

        // Recepción exacta del manifiesto: 10 + 10 bultos, 100 + 100 unidades, 1.000 kg
        $this->bulto([[$this->a, '10']], peso: '50', repetir: 10, pallet: $abierto)->assertCreated();
        $this->bulto([[$this->b, '10']], peso: '50', repetir: 10, pallet: $abierto)->assertCreated();

        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/finalizar")
            ->assertOk()
            ->assertJsonPath('data.bl.estado', 'RECIBIDO')
            ->assertJsonPath('data.peso_kg.dentro_de_tolerancia', true)
            ->assertJsonPath('data.requiere_observacion', false);

        $this->assertSame(EstadoPallet::CERRADO, Pallet::where('codigo', $abierto)->firstOrFail()->estado); // R10: abierto con bultos se cierra
        $this->assertNull(Pallet::where('codigo', $vacio)->first());                                    // R10: abierto vacío se elimina
        $this->assertSame(0, $this->bl->refresh()->discrepancia_bultos);

        // D3 / R11: un movimiento RECEPCION por línea; sin bodega todavía (se ubica en CUS-008)
        $this->assertSame(20, Movimiento::where('tipo', 'RECEPCION')->count());
        $this->assertSame(200000, Milesimas::de((string) Movimiento::sum('cantidad')));
        $this->assertSame(0, Existencia::count());

        // D4 / D8: recibido, ya no entran bultos
        $this->bulto([[$this->a, '1']])->assertStatus(422)->assertJsonPath('codigo', 'BL_NO_EN_RECEPCION');
    }

    public function test_pallet_ya_ubicado_lleva_la_recepcion_a_su_bodega(): void
    {
        $this->iniciar();
        $codigo = $this->palletNuevo();
        $this->bulto([[$this->a, '10']], repetir: 10, pallet: $codigo);
        $this->bulto([[$this->b, '10']], repetir: 10, pallet: $codigo);

        // D10: se cierra y se ubica antes de finalizar (CUS-008 llegará en el Paso 5)
        $pallet = Pallet::where('codigo', $codigo)->firstOrFail();
        $bodega = $this->crearBodega();
        $pallet->cerrar();
        $pallet->ubicar($bodega);
        $pallet->save();

        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/finalizar")->assertOk();

        $this->assertSame(20, Movimiento::where('bodega_id', $bodega->id)->count());
        $this->assertSame(200000, Milesimas::de((string) Existencia::where('bodega_id', $bodega->id)->sum('cantidad_cuarentena')));
    }

    public function test_peso_fuera_de_tolerancia_exige_observacion(): void
    {
        $this->iniciar();
        $pallet = $this->palletNuevo();
        // Bultos y unidades exactos, pero 1.200 kg contra 1.000 esperados (+20 %)
        $this->bulto([[$this->a, '10']], peso: '60', repetir: 10, pallet: $pallet);
        $this->bulto([[$this->b, '10']], peso: '60', repetir: 10, pallet: $pallet);

        $this->apiGet($this->operario, "/api/v1/recepciones/{$this->bl->id}")
            ->assertJsonPath('data.peso_kg.dentro_de_tolerancia', false)
            ->assertJsonPath('data.requiere_observacion', true);

        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/finalizar")
            ->assertStatus(422)->assertJsonPath('codigo', 'OBSERVACION_REQUERIDA');

        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/finalizar", ['observacion' => 'Estibas de madera más pesadas.'])
            ->assertOk()->assertJsonPath('data.bl.estado', 'RECIBIDO');
    }

    public function test_los_bultos_anulados_no_entran_al_inventario(): void
    {
        $this->iniciar();
        $pallet = $this->palletNuevo();
        $this->bulto([[$this->a, '10']], repetir: 10, pallet: $pallet);
        $this->bulto([[$this->b, '10']], repetir: 10, pallet: $pallet);
        $anulado = $this->bulto([[$this->a, '99']], pallet: $pallet)->json('data.bultos.0.codigo');
        $this->apiPost($this->operario, "/api/v1/bultos/{$anulado}/anular")->assertOk();

        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/finalizar")->assertOk();

        $this->assertSame(20, Movimiento::count());
        $this->assertSame(EstadoBulto::ANULADO, Bulto::where('codigo', $anulado)->first()->estado);
    }
}

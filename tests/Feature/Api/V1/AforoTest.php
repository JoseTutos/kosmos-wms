<?php

namespace Tests\Feature\Api\V1;

use App\Enums\CodigoError;
use App\Enums\Rol;
use App\Models\Aforo;
use App\Models\Bl;
use App\Models\Bodega;
use App\Models\Bulto;
use App\Models\Existencia;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\Serial;
use App\Models\User;
use App\Services\Inbound\AforoService;
use App\Support\Milesimas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** CUS-009 aforo: activar (web) → contar y serializar (app) → cerrar o cancelar (D2, D7b, A1–A9). */
final class AforoTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase, UsaApi;

    private User $operario;

    private User $supervisor;

    private Producto $producto;

    private Bodega $bodega;

    private Bl $bl;

    private AforoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->operario = $this->crearUsuario(Rol::OPERARIO);
        $this->supervisor = $this->crearUsuario(Rol::SUPERVISOR);
        $cliente = $this->crearCliente();
        $this->producto = $this->crearProducto($cliente);
        $this->bodega = $this->crearBodega();
        $this->bl = $this->blRecibido($cliente, 2);
        $this->servicio = app(AforoService::class);
    }

    /** BL recibido con N bultos de 10 UND en un pallet ubicado (por la API, como en planta). */
    private function blRecibido($cliente, int $bultos): Bl
    {
        $bl = $this->crearBl($cliente, $this->producto);
        $bl->iniciarRecepcion();
        $bl->save();

        $pallet = $this->crearPallet($bl);
        for ($i = 0; $i < $bultos; $i++) {
            $bulto = $this->crearBulto($bl, $this->producto, '10');
            $pallet->agregarBulto($bulto);
            $bulto->save();
        }
        $pallet->cerrar();
        $pallet->save();

        $this->apiPost($this->operario, '/api/v1/almacenamiento/ubicar', [
            'pallet' => $pallet->codigo, 'bodega' => $this->bodega->codigo, 'escaneo' => $this->bodega->codigo,
        ])->assertOk();

        $this->apiPost($this->operario, "/api/v1/recepciones/{$bl->id}/finalizar", ['observacion' => 'Prueba de aforo.'])->assertOk();

        return $bl->refresh();
    }

    private function activar(): Aforo
    {
        return $this->servicio->activar($this->bl, $this->bl->bultos()->pluck('id')->all(), $this->supervisor);
    }

    private function contar(Aforo $aforo, int $detalleId, string $cantidad)
    {
        return $this->apiPost($this->operario, "/api/v1/aforos/{$aforo->id}/conteos", ['detalle_id' => $detalleId, 'cantidad' => $cantidad]);
    }

    public function test_activar_exige_bl_recibido_bultos_ubicados_y_un_aforo_por_bl(): void
    {
        $aforo = $this->activar();

        $this->assertSame(2, $aforo->detalles()->count());
        $this->assertSame(['EN_AFORO'], $this->bl->bultos()->pluck('estado')->map->value->unique()->values()->all());

        $this->assertRegla(CodigoError::AFORO_EN_PROCESO_EXISTENTE, fn () => $this->activar());

        $nuevo = $this->crearBl($this->bl->cliente, $this->producto);
        $this->assertRegla(CodigoError::BL_ESTADO_INVALIDO, fn () => $this->servicio->activar($nuevo, [1], $this->supervisor));
    }

    public function test_bultos_con_reserva_no_entran(): void
    {
        $linea = $this->bl->bultoDetalles()->first();
        $linea->legalizar('5');
        $linea->reservar('2');
        $linea->save();

        $this->assertRegla(CodigoError::AFORO_LINEA_RESERVADA, fn () => $this->activar());
    }

    public function test_conteo_y_cierre_corrigen_cantidades_e_inventario(): void
    {
        $aforo = $this->activar();
        [$d1, $d2] = $aforo->detalles()->orderBy('id')->pluck('id')->all();

        $this->apiGet($this->operario, '/api/v1/aforos')->assertOk()->assertJsonPath('data.0.lineas_total', 2);

        $this->contar($aforo, $d1, '8')
            ->assertOk()
            ->assertJsonPath('data.detalle.diferencia', '-2.000')
            ->assertJsonPath('data.avance.lineas_contadas', 1);

        $this->assertRegla(CodigoError::AFORO_INCOMPLETO, fn () => $this->servicio->cerrar($aforo, $this->supervisor));

        $this->contar($aforo, $d2, '10')->assertOk();

        $this->apiGet($this->operario, "/api/v1/aforos/{$aforo->id}")
            ->assertOk()
            ->assertJsonPath('data.lineas_contadas', 2)
            ->assertJsonPath('data.detalles.0.serializable', true);

        $cerrado = $this->servicio->cerrar($aforo, $this->supervisor);

        $this->assertSame('CERRADO', $cerrado->estado->value);
        $this->assertSame('-2.000', Movimiento::where('tipo', 'AFORO')->value('cantidad'));
        $this->assertSame(1, Movimiento::where('tipo', 'AFORO')->count());   // la línea sin diferencia no mueve inventario
        $this->assertSame(18000, Milesimas::de((string) Existencia::where('bodega_id', $this->bodega->id)->sum('cantidad_cuarentena')));
        $this->assertSame(['8.000', '10.000'], $this->bl->bultoDetalles()->orderBy('id')->pluck('cantidad_recibida')->all());
        $this->assertSame(['AFORADO'], $this->bl->bultos()->pluck('estado')->map->value->unique()->values()->all());

        Storage::disk('local')->assertExists($cerrado->reporte_path);
        Storage::disk('local')->assertExists(AforoService::rutaExcel($cerrado->reporte_path));

        $this->contar($aforo, $d1, '9')->assertStatus(422)->assertJsonPath('codigo', 'AFORO_NO_EN_PROCESO');
    }

    public function test_seriales_solo_und_y_se_regeneran_si_cambia_el_conteo(): void
    {
        $aforo = $this->activar();
        [$d1, $d2] = $aforo->detalles()->orderBy('id')->pluck('id')->all();

        $this->apiPost($this->operario, "/api/v1/aforos/{$aforo->id}/seriales", ['detalle_id' => $d1])
            ->assertStatus(422)->assertJsonPath('codigo', 'CONTEO_REQUERIDO');

        $this->contar($aforo, $d1, '3.5')->assertStatus(422)->assertJsonPath('codigo', 'CANTIDAD_INVALIDA');   // UND: entero
        $this->contar($aforo, $d1, '3')->assertOk();

        $codigos = $this->apiPost($this->operario, "/api/v1/aforos/{$aforo->id}/seriales", ['detalle_id' => $d1])
            ->assertCreated()
            ->assertJsonPath('data.total', 3)
            ->json('data.seriales.*.codigo');

        foreach ($codigos as $codigo) {
            $this->assertMatchesRegularExpression('/^'.preg_quote($this->producto->sku, '/').'-\d{4}$/', $codigo);
        }

        // Cambiar el conteo descarta los seriales: hay que regenerarlos
        $this->contar($aforo, $d1, '2')->assertOk()->assertJsonPath('data.detalle.seriales', 0);
        $this->apiPost($this->operario, "/api/v1/aforos/{$aforo->id}/seriales", ['detalle_id' => $d1])->assertCreated()->assertJsonPath('data.total', 2);

        $this->contar($aforo, $d2, '10')->assertOk();
        $this->servicio->cerrar($aforo, $this->supervisor);

        $this->assertTrue($this->bl->bultoDetalles()->orderBy('id')->first()->serializado);
        $this->assertSame(2, Serial::count());

        // Otro producto en KG no se serializa
        $this->producto->forceFill(['unidad_medida' => 'KG'])->save();
        $otro = $this->blRecibido($this->bl->cliente, 1);
        $aforoKg = $this->servicio->activar($otro, $otro->bultos()->pluck('id')->all(), $this->supervisor);
        $detalle = $aforoKg->detalles()->value('id');
        $this->contar($aforoKg, $detalle, '9.5')->assertOk();
        $this->apiPost($this->operario, "/api/v1/aforos/{$aforoKg->id}/seriales", ['detalle_id' => $detalle])
            ->assertStatus(422)->assertJsonPath('codigo', 'SERIAL_SOLO_UND');
    }

    public function test_conteo_menor_a_lo_legalizado_bloquea_el_cierre(): void
    {
        $linea = $this->bl->bultoDetalles()->orderBy('id')->first();
        $linea->legalizar('6');
        $linea->save();

        $aforo = $this->activar();
        [$d1, $d2] = $aforo->detalles()->orderBy('id')->pluck('id')->all();
        $this->contar($aforo, $d1, '4')->assertOk();
        $this->contar($aforo, $d2, '10')->assertOk();

        $this->assertRegla(CodigoError::CONTEO_MENOR_A_LEGALIZADO, fn () => $this->servicio->cerrar($aforo, $this->supervisor));

        $this->assertSame('EN_PROCESO', $aforo->refresh()->estado->value);   // nada se aplicó
        $this->assertSame('10.000', $linea->refresh()->cantidad_recibida);
    }

    public function test_cancelar_descarta_conteos_y_seriales(): void
    {
        $aforo = $this->activar();
        $d1 = $aforo->detalles()->orderBy('id')->value('id');
        $this->contar($aforo, $d1, '5')->assertOk();
        $this->apiPost($this->operario, "/api/v1/aforos/{$aforo->id}/seriales", ['detalle_id' => $d1])->assertCreated();

        $this->servicio->cancelar($aforo, $this->supervisor);

        $this->assertSame('CANCELADO', $aforo->refresh()->estado->value);
        $this->assertSame(0, Serial::count());
        $this->assertSame(0, Movimiento::where('tipo', 'AFORO')->count());
        $this->assertSame(['UBICADO'], Bulto::where('bl_id', $this->bl->id)->pluck('estado')->map->value->unique()->values()->all());

        // Se puede activar otro aforo sobre el mismo BL
        $this->assertSame('EN_PROCESO', $this->activar()->estado->value);
    }

    public function test_linea_de_otro_aforo_se_rechaza(): void
    {
        $aforo = $this->activar();

        $this->contar($aforo, 999999, '1')->assertStatus(422)->assertJsonPath('codigo', 'DETALLE_OTRO_AFORO');
    }
}

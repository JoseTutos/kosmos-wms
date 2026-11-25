<?php

namespace Tests\Feature\Web;

use App\Enums\CodigoError;
use App\Enums\EstadoLegal;
use App\Enums\EstadoLegalSerial;
use App\Enums\Rol;
use App\Enums\TipoMovimiento;
use App\Models\Bl;
use App\Models\Bodega;
use App\Models\Bulto;
use App\Models\BultoDetalle;
use App\Models\Cliente;
use App\Models\Existencia;
use App\Models\Legalizacion;
use App\Models\LegalizacionDetalle;
use App\Models\LegalizacionSerial;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\Serial;
use App\Models\User;
use App\Services\Control\LegalizacionService;
use App\Services\Nucleo\ConsecutivoService;
use App\Services\Nucleo\MovimientoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

/** CUS-010 Registrar Legalización (web, Analista y Supervisor). Decisiones L1–L21. */
final class LegalizacionTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    private User $analista;

    private Cliente $cliente;

    private Producto $producto;

    private Producto $kilos;

    private Bodega $bodega;

    private Bl $bl;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->analista = $this->crearUsuario(Rol::ANALISTA);
        $this->cliente = $this->crearCliente();
        $this->producto = $this->crearProducto($this->cliente);
        $this->kilos = $this->crearProducto($this->cliente);
        $this->kilos->forceFill(['unidad_medida' => 'KG'])->save();
        $this->bodega = $this->crearBodega();
        $this->bl = $this->blRecibido();

        $this->actingAs($this->analista);
    }

    // ------------------------------------------------------------ escenario

    private function blRecibido(): Bl
    {
        $bl = $this->crearBl($this->cliente, $this->producto, $this->kilos);
        $bl->iniciarRecepcion();
        $bl->finalizarRecepcion(null);
        $bl->save();

        return $bl;
    }

    /** Bulto en un pallet (ubicado o no) con su RECEPCION en el kardex, como al finalizar el BL. */
    private function bulto(string $cantidad = '10', ?Producto $producto = null, bool $ubicar = true, ?Bl $bl = null): Bulto
    {
        $bl ??= $this->bl;
        $pallet = $this->crearPallet($bl);
        $bulto = $this->crearBulto($bl, $producto ?? $this->producto, $cantidad);

        $pallet->agregarBulto($bulto);
        $bulto->save();
        $pallet->cerrar();
        if ($ubicar) {
            $pallet->ubicar($this->bodega);
        }
        $pallet->save();

        $linea = $bulto->detalles()->firstOrFail();
        DB::transaction(fn () => app(MovimientoService::class)->registrar(
            TipoMovimiento::RECEPCION, $linea, $cantidad, $ubicar ? $this->bodega->id : null, EstadoLegal::CUARENTENA, $this->analista, 'BL-PRUEBA',
        ));

        return $bulto->refresh();
    }

    /** Seriales del aforo (todos en cuarentena) para la línea del bulto. */
    private function serializar(Bulto $bulto): Collection
    {
        $linea = $this->linea($bulto);
        $linea->serializado = true;
        $linea->save();

        $unidades = (int) $linea->cantidad_recibida;

        return collect(range(1, $unidades))->map(fn () => Serial::create([
            'codigo'           => app(ConsecutivoService::class)->serial($this->producto->sku),
            'bulto_detalle_id' => $linea->id,
        ]));
    }

    private function linea(Bulto $bulto): BultoDetalle
    {
        return $bulto->detalles()->firstOrFail();
    }

    /** @param list<array<string, mixed>> $carrito */
    private function enviar(array $carrito, array $cambios = []): TestResponse
    {
        return $this->post(route('legalizaciones.store'), array_merge([
            'bl_id'               => $this->bl->id,
            'carrito'             => json_encode($carrito),
            'tipo_documento'      => 'RESOLUCION_NACIONALIZACION',
            'numero_autorizacion' => 'RES-2026-001',
            'fecha_autorizacion'  => '2026-11-20',
            'soporte'             => UploadedFile::fake()->create('resolucion.pdf', 200, 'application/pdf'),
        ], $cambios));
    }

    private function assertRechazo(CodigoError $codigo, TestResponse $respuesta): void
    {
        $respuesta->assertRedirect()->assertSessionHasErrors('negocio');

        $mensaje = session('errors')->first('negocio');
        $this->assertStringContainsString($codigo->mensaje(), $mensaje, "Se esperaba {$codigo->value} y llegó: {$mensaje}");
    }

    private function existencia(): Existencia
    {
        return Existencia::where('bodega_id', $this->bodega->id)->where('producto_id', $this->producto->id)->firstOrFail();
    }

    private function soportesGuardados(): int
    {
        return count(Storage::disk('local')->allFiles(LegalizacionService::CARPETA));
    }

    private function assertNadaRegistrado(): void
    {
        $this->assertSame(0, Legalizacion::count());
        $this->assertSame(0, Movimiento::where('tipo', TipoMovimiento::LEGALIZACION->value)->count());
        $this->assertSame(0, $this->soportesGuardados(), 'El soporte debe borrarse si la transacción falla (L14).');
    }

    // ------------------------------------------------------------ modos

    public function test_modo_a_legaliza_el_bulto_completo(): void
    {
        $bulto = $this->bulto('10');

        $this->enviar([['modo' => 'A', 'bulto_id' => $bulto->id]])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('limpiar_carrito', $this->bl->id);

        $leg = Legalizacion::with('detalles')->sole();

        $this->assertMatchesRegularExpression('/^LEG-\d{4}-00001$/', $leg->consecutivo);
        $this->assertSame($this->analista->id, $leg->usuario_id);
        $this->assertSame('10.000', $leg->detalles->sole()->cantidad);
        $this->assertSame('10.000', $this->linea($bulto)->cantidad_legalizada);
        $this->assertSame('0.000', $this->existencia()->cantidad_cuarentena);
        $this->assertSame('10.000', $this->existencia()->cantidad_legalizada);

        $movimientos = Movimiento::where('tipo', TipoMovimiento::LEGALIZACION->value)->orderBy('id')->get();
        $this->assertSame(['-10.000', '10.000'], $movimientos->pluck('cantidad')->all());
        $this->assertSame([$leg->consecutivo], $movimientos->pluck('documento_ref')->unique()->values()->all());

        Storage::disk('local')->assertExists($leg->soporte_path);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'legalizacion', 'subject_id' => $leg->id]);
    }

    public function test_modo_b_por_cantidad_consolida_la_misma_linea(): void
    {
        $bulto = $this->bulto('10');
        $linea = $this->linea($bulto);

        $this->enviar([
            ['modo' => 'B', 'linea_id' => $linea->id, 'cantidad' => '2'],
            ['modo' => 'B', 'linea_id' => $linea->id, 'cantidad' => '3'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('5.000', LegalizacionDetalle::sole()->cantidad);   // L7: una fila por línea
        $this->assertSame('5.000', $linea->fresh()->en_cuarentena);
        $this->assertSame('5.000', $this->existencia()->cantidad_cuarentena);
    }

    public function test_modo_b_respeta_la_unidad_de_medida(): void
    {
        $und = $this->linea($this->bulto('10'));
        $kg = $this->linea($this->bulto('2.5', $this->kilos));

        $this->assertRechazo(CodigoError::CANTIDAD_NO_ENTERA, $this->enviar([['modo' => 'B', 'linea_id' => $und->id, 'cantidad' => '1,5']]));
        $this->assertNadaRegistrado();

        $this->enviar([['modo' => 'B', 'linea_id' => $kg->id, 'cantidad' => '2,125']])->assertSessionHasNoErrors();
        $this->assertSame('0.375', $kg->fresh()->en_cuarentena);
    }

    public function test_modo_a_en_linea_serializada_incluye_sus_seriales(): void
    {
        $bulto = $this->bulto('3');
        $this->serializar($bulto);

        $this->enviar([['modo' => 'A', 'bulto_id' => $bulto->id]])->assertSessionHasNoErrors();

        $this->assertSame('3.000', LegalizacionDetalle::sole()->cantidad);
        $this->assertSame(3, LegalizacionSerial::count());
        $this->assertSame(0, Serial::where('estado_legal', EstadoLegalSerial::EN_CUARENTENA->value)->count());
    }

    public function test_modo_c_legaliza_solo_los_seriales_elegidos(): void
    {
        $bulto = $this->bulto('3');
        $seriales = $this->serializar($bulto);

        $this->enviar([['modo' => 'C', 'linea_id' => $this->linea($bulto)->id, 'seriales' => $seriales->take(2)->pluck('id')->all()]])
            ->assertSessionHasNoErrors();

        $this->assertSame('2.000', LegalizacionDetalle::sole()->cantidad);
        $this->assertEqualsCanonicalizing($seriales->take(2)->pluck('id')->all(), LegalizacionSerial::pluck('serial_id')->all());
        $this->assertSame(EstadoLegalSerial::EN_CUARENTENA, $seriales->last()->fresh()->estado_legal);
        $this->assertSame('1.000', $this->linea($bulto)->en_cuarentena);
    }

    public function test_cada_linea_admite_solo_su_modo(): void
    {
        $normal = $this->bulto('10');
        $serializado = $this->bulto('3');
        $seriales = $this->serializar($serializado);

        // FA-02: Modo C en una línea sin seriales
        $this->assertRechazo(CodigoError::LINEA_NO_SERIALIZADA, $this->enviar([
            ['modo' => 'C', 'linea_id' => $this->linea($normal)->id, 'seriales' => [$seriales->first()->id]],
        ]));
        // L6: la parcial de una línea serializada solo va por seriales
        $this->assertRechazo(CodigoError::LINEA_SERIALIZADA, $this->enviar([
            ['modo' => 'B', 'linea_id' => $this->linea($serializado)->id, 'cantidad' => '1'],
        ]));

        $this->assertNadaRegistrado();
    }

    public function test_mezcla_de_modos_en_una_sola_legalizacion(): void
    {
        $completo = $this->bulto('10');
        $parcial = $this->bulto('10');
        $conSeriales = $this->bulto('3');
        $seriales = $this->serializar($conSeriales);

        $this->enviar([
            ['modo' => 'A', 'bulto_id' => $completo->id],
            ['modo' => 'B', 'linea_id' => $this->linea($parcial)->id, 'cantidad' => '4'],
            ['modo' => 'C', 'linea_id' => $this->linea($conSeriales)->id, 'seriales' => [$seriales->first()->id]],
        ])->assertSessionHasNoErrors();

        $leg = Legalizacion::sole();
        $this->assertSame(3, $leg->detalles()->count());
        $this->assertSame(1, $leg->seriales()->count());
        $this->assertSame(6, Movimiento::where('tipo', TipoMovimiento::LEGALIZACION->value)->where('documento_ref', $leg->consecutivo)->count());
        $this->assertSame('15.000', $this->existencia()->cantidad_legalizada);
        $this->assertSame('8.000', $this->existencia()->cantidad_cuarentena);
    }

    // ------------------------------------------------------------ revalidación (L10, L16, D8)

    public function test_el_servidor_revalida_lo_que_otro_ya_legalizo(): void
    {
        $bulto = $this->bulto('10');
        $carrito = [['modo' => 'B', 'linea_id' => $this->linea($bulto)->id, 'cantidad' => '6']];

        $this->enviar($carrito)->assertSessionHasNoErrors();
        $this->assertRechazo(CodigoError::CANTIDAD_SUPERA_CUARENTENA, $this->enviar($carrito));   // FA-04

        $this->assertSame(1, Legalizacion::count());
        $this->assertSame(1, $this->soportesGuardados());
        $this->assertSame('6.000', $this->linea($bulto)->cantidad_legalizada);
    }

    public function test_bulto_o_serial_ya_legalizado_se_rechaza(): void
    {
        $bulto = $this->bulto('10');
        $conSeriales = $this->bulto('2');
        $seriales = $this->serializar($conSeriales);
        $serialC = [['modo' => 'C', 'linea_id' => $this->linea($conSeriales)->id, 'seriales' => [$seriales->first()->id]]];

        $this->enviar([['modo' => 'A', 'bulto_id' => $bulto->id], ...$serialC])->assertSessionHasNoErrors();

        $this->assertRechazo(CodigoError::BULTO_YA_LEGALIZADO, $this->enviar([['modo' => 'A', 'bulto_id' => $bulto->id]]));   // FA-01
        $this->assertRechazo(CodigoError::SERIAL_YA_LEGALIZADO, $this->enviar($serialC));                                   // FA-01
        $this->assertSame(1, Legalizacion::count());
    }

    // ------------------------------------------------------------ exclusiones (L2, L3)

    public function test_bulto_en_aforo_queda_excluido(): void
    {
        $bulto = $this->bulto('10');
        $bulto->iniciarAforo();
        $bulto->save();

        $this->assertRechazo(CodigoError::BULTO_EN_AFORO, $this->enviar([['modo' => 'A', 'bulto_id' => $bulto->id]]));
        $this->assertNadaRegistrado();

        $datos = app(LegalizacionService::class)->cargarBl($this->bl);
        $this->assertSame(CodigoError::BULTO_EN_AFORO->value, $datos['bultos'][0]['bloqueo']);
    }

    public function test_bulto_de_pallet_sin_ubicar_queda_excluido(): void
    {
        $bulto = $this->bulto('10', ubicar: false);

        $this->assertRechazo(CodigoError::PALLET_SIN_UBICAR, $this->enviar([
            ['modo' => 'B', 'linea_id' => $this->linea($bulto)->id, 'cantidad' => '1'],
        ]));
        $this->assertNadaRegistrado();
    }

    public function test_bultos_anulados_no_se_cargan(): void
    {
        $vigente = $this->bulto('10');
        $anulado = $this->crearBulto($this->bl, $this->producto);
        $anulado->anular();
        $anulado->save();

        $codigos = array_column(app(LegalizacionService::class)->cargarBl($this->bl)['bultos'], 'codigo');

        $this->assertSame([$vigente->codigo], $codigos);
    }

    public function test_bl_no_legalizable_o_bulto_de_otro_bl(): void
    {
        $programado = $this->crearBl($this->cliente, $this->producto);
        $deOtro = $this->bulto('10', bl: $this->blRecibido());

        $this->assertRechazo(CodigoError::BL_NO_LEGALIZABLE, $this->enviar([['modo' => 'A', 'bulto_id' => $deOtro->id]], ['bl_id' => $programado->id]));
        $this->assertRechazo(CodigoError::LEGALIZACION_OTRO_BL, $this->enviar([['modo' => 'A', 'bulto_id' => $deOtro->id]]));
        $this->assertNadaRegistrado();
    }

    // ------------------------------------------------------------ autorización (L11–L14)

    public function test_autorizacion_y_soporte_son_obligatorios(): void
    {
        $carrito = [['modo' => 'A', 'bulto_id' => $this->bulto('10')->id]];

        $this->enviar($carrito, ['soporte' => null])->assertSessionHasErrors('soporte');
        $this->enviar($carrito, ['soporte' => UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('soporte');
        $this->enviar($carrito, ['soporte' => UploadedFile::fake()->create('grande.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('soporte');
        $this->enviar($carrito, ['tipo_documento' => 'CARTA'])->assertSessionHasErrors('tipo_documento');
        $this->enviar($carrito, ['numero_autorizacion' => str_repeat('9', 61)])->assertSessionHasErrors('numero_autorizacion');
        $this->enviar($carrito, ['fecha_autorizacion' => 'ayer'])->assertSessionHasErrors('fecha_autorizacion');
        $this->enviar([])->assertSessionHasErrors('items');
        $this->post(route('legalizaciones.store'), ['bl_id' => $this->bl->id, 'carrito' => '{no es json'])->assertSessionHasErrors('items');

        $this->assertNadaRegistrado();
    }

    public function test_numero_de_autorizacion_repetido_avisa_sin_bloquear(): void
    {
        $a = $this->bulto('10');
        $b = $this->bulto('10');

        $this->enviar([['modo' => 'A', 'bulto_id' => $a->id]])->assertSessionMissing('aviso');
        $primera = Legalizacion::sole();

        $this->enviar([['modo' => 'A', 'bulto_id' => $b->id]], ['numero_autorizacion' => ' res-2026-001 '])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('aviso', fn (string $aviso) => str_contains($aviso, $primera->consecutivo));

        $this->assertSame(2, Legalizacion::count());

        $this->getJson(route('legalizaciones.autorizacion', ['numero' => 'RES-2026-001']))
            ->assertOk()
            ->assertJsonCount(2, 'usos');
    }

    // ------------------------------------------------------------ pantalla, detalle e inmutabilidad

    public function test_la_pantalla_carga_los_bultos_y_seriales_en_cuarentena(): void
    {
        $bulto = $this->bulto('2');
        $seriales = $this->serializar($bulto);

        $this->get(route('legalizaciones.create'))->assertOk()->assertSee($this->bl->numero_bl);
        $this->get(route('legalizaciones.create', ['bl' => $this->bl->id]))
            ->assertOk()
            ->assertSee($bulto->codigo)
            ->assertSee($seriales->first()->codigo);

        $linea = app(LegalizacionService::class)->cargarBl($this->bl)['bultos'][0]['lineas'][0];
        $this->assertTrue($linea['serializado']);
        $this->assertSame('2.000', $linea['en_cuarentena']);
        $this->assertCount(2, $linea['seriales']);
    }

    public function test_detalle_descarga_del_soporte_y_sin_edicion(): void
    {
        $this->enviar([['modo' => 'A', 'bulto_id' => $this->bulto('10')->id]]);
        $leg = Legalizacion::sole();

        $this->get(route('legalizaciones.index'))->assertOk()->assertSee($leg->consecutivo);
        $this->get(route('legalizaciones.show', $leg))->assertOk()->assertSee($leg->consecutivo);
        $this->get(route('legalizaciones.soporte', $leg))->assertOk()->assertDownload("soporte-{$leg->consecutivo}.pdf");

        // L18 / RS-48: no hay rutas para editar ni borrar
        $this->put(route('legalizaciones.show', $leg))->assertStatus(405);
        $this->delete(route('legalizaciones.show', $leg))->assertStatus(405);
        $this->assertModelExists($leg);
    }

    // ------------------------------------------------------------ permisos (L1)

    public function test_el_supervisor_tambien_legaliza(): void
    {
        $this->actingAs($this->crearUsuario(Rol::SUPERVISOR));

        $this->get(route('legalizaciones.index'))->assertOk();
        $this->enviar([['modo' => 'A', 'bulto_id' => $this->bulto('10')->id]])->assertSessionHasNoErrors();

        $this->assertSame(1, Legalizacion::count());
    }

    #[DataProvider('otrosRoles')]
    public function test_otros_roles_no_legalizan(string $rol): void
    {
        $bulto = $this->bulto('10');
        $this->actingAs($this->crearUsuario(Rol::from($rol)));

        $this->get(route('legalizaciones.index'))->assertForbidden();
        $this->get(route('legalizaciones.create'))->assertForbidden();
        $this->enviar([['modo' => 'A', 'bulto_id' => $bulto->id]])->assertForbidden();

        $this->assertNadaRegistrado();
    }

    public static function otrosRoles(): array
    {
        return [['OPERARIO'], ['GERENTE'], ['ADMIN_MAESTROS'], ['ADMIN_SISTEMA']];
    }
}

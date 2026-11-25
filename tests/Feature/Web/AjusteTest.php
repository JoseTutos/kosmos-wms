<?php

namespace Tests\Feature\Web;

use App\Enums\CodigoError;
use App\Enums\EstadoLegal;
use App\Enums\Rol;
use App\Enums\TipoMovimiento;
use App\Models\Ajuste;
use App\Models\Bl;
use App\Models\Bodega;
use App\Models\Bulto;
use App\Models\BultoDetalle;
use App\Models\Existencia;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\User;
use App\Services\Control\AjusteService;
use App\Services\Inbound\AforoService;
use App\Services\Nucleo\MovimientoService;
use App\Support\Milesimas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

/** CUS-019 Registrar Ajuste de Inventario (web, Supervisor) + atajo desde el aforo (J1–J10, A1–A3). */
final class AjusteTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    private User $supervisor;

    private Producto $producto;

    private Bodega $bodega;

    private Bl $bl;

    private Bulto $bulto;

    private BultoDetalle $linea;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->supervisor = $this->crearUsuario(Rol::SUPERVISOR);
        $cliente = $this->crearCliente();
        $this->producto = $this->crearProducto($cliente);
        $this->bodega = $this->crearBodega();

        $this->bl = $this->crearBl($cliente, $this->producto);
        $this->bl->iniciarRecepcion();
        $this->bl->finalizarRecepcion(null);
        $this->bl->save();

        $this->bulto = $this->bultoUbicado('10');
        $this->linea = $this->bulto->detalles()->firstOrFail();

        $this->actingAs($this->supervisor);
    }

    // ------------------------------------------------------------ escenario

    private function bultoUbicado(string $cantidad): Bulto
    {
        $pallet = $this->crearPallet($this->bl);
        $bulto = $this->crearBulto($this->bl, $this->producto, $cantidad);

        $pallet->agregarBulto($bulto);
        $bulto->save();
        $pallet->cerrar();
        $pallet->ubicar($this->bodega);
        $pallet->save();
        $bulto->marcarUbicado();
        $bulto->save();

        $linea = $bulto->detalles()->firstOrFail();
        DB::transaction(fn () => app(MovimientoService::class)->registrar(
            TipoMovimiento::RECEPCION, $linea, $cantidad, $this->bodega->id, EstadoLegal::CUARENTENA, $this->supervisor, 'BL-PRUEBA',
        ));

        return $bulto->refresh();
    }

    private function legalizar(string $cantidad): void
    {
        DB::transaction(function () use ($cantidad) {
            $linea = BultoDetalle::query()->lockForUpdate()->findOrFail($this->linea->id);
            $q = $linea->legalizarPorCantidad($cantidad);
            $linea->save();
            app(MovimientoService::class)->legalizar($linea, $q, $this->bodega->id, $this->supervisor, 'LEG-2026-99999');
        });
    }

    private function enviar(string $tipo, string $estado, string $cantidad, array $cambios = [], ?BultoDetalle $linea = null): TestResponse
    {
        return $this->post(route('ajustes.store'), array_merge([
            'bulto_detalle_id' => ($linea ?? $this->linea)->id,
            'tipo'             => $tipo,
            'estado_afectado'  => $estado,
            'cantidad'         => $cantidad,
            'motivo'           => 'ERROR_CONTEO',
            'descripcion'      => 'Diferencia detectada en el recorrido de control.',
            'confirmacion'     => '1',
        ], $cambios));
    }

    private function assertRechazo(CodigoError $codigo, TestResponse $respuesta): void
    {
        $respuesta->assertRedirect()->assertSessionHasErrors('negocio');

        $mensaje = session('errors')->first('negocio');
        $this->assertStringContainsString($codigo->mensaje(), $mensaje, "Se esperaba {$codigo->value} y llegó: {$mensaje}");
    }

    /** [recibida, legalizada] de la línea y [cuarentena, legalizada] de la existencia. */
    private function assertCantidades(string $recibida, string $legalizada): void
    {
        $linea = $this->linea->fresh();
        $existencia = Existencia::where('bodega_id', $this->bodega->id)->firstOrFail();

        $this->assertSame($recibida, $linea->cantidad_recibida, 'recibida');
        $this->assertSame($legalizada, $linea->cantidad_legalizada, 'legalizada');
        $this->assertSame($linea->en_cuarentena, $existencia->cantidad_cuarentena, 'existencia en cuarentena');
        $this->assertSame($legalizada, $existencia->cantidad_legalizada, 'existencia legalizada');
        $this->assertSame(
            Milesimas::de($recibida),
            Milesimas::de((string) Movimiento::where('bulto_detalle_id', $this->linea->id)->sum('cantidad')),
            'El saldo del kardex debe seguir igual a la recibida.',
        );
    }

    private function assertSinAjustes(): void
    {
        $this->assertSame(0, Ajuste::count());
        $this->assertSame(0, Movimiento::where('tipo', TipoMovimiento::AJUSTE->value)->count());
        $this->assertSame(0, count(Storage::disk('local')->allFiles(AjusteService::CARPETA)));
    }

    // ------------------------------------------------------------ positivo y negativo en cada estado (J2)

    public function test_positivo_y_negativo_en_cuarentena(): void
    {
        $this->enviar('POSITIVO', 'CUARENTENA', '2')->assertSessionHasNoErrors();

        $ajuste = Ajuste::sole();
        $this->assertMatchesRegularExpression('/^AJ-\d{4}-00001$/', $ajuste->consecutivo);
        $this->assertCantidades('12.000', '0.000');

        $movimiento = Movimiento::where('tipo', TipoMovimiento::AJUSTE->value)->sole();
        $this->assertSame('2.000', $movimiento->cantidad);
        $this->assertSame(EstadoLegal::CUARENTENA, $movimiento->estado_legal);
        $this->assertSame($ajuste->consecutivo, $movimiento->documento_ref);
        $this->assertSame($this->bodega->id, $movimiento->bodega_id);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'ajustes', 'subject_id' => $ajuste->id]);

        $this->enviar('NEGATIVO', 'CUARENTENA', '5')->assertSessionHasNoErrors();
        $this->assertCantidades('7.000', '0.000');
    }

    public function test_positivo_y_negativo_en_legalizada(): void
    {
        $this->legalizar('6');

        $this->enviar('POSITIVO', 'LEGALIZADA', '1')->assertSessionHasNoErrors();
        $this->assertCantidades('11.000', '7.000');

        $this->enviar('NEGATIVO', 'LEGALIZADA', '3')->assertSessionHasNoErrors();
        $this->assertCantidades('8.000', '4.000');
    }

    // ------------------------------------------------------------ bloqueos (J3, EX-01, EX-02)

    public function test_un_ajuste_que_deja_negativos_se_bloquea(): void
    {
        $this->legalizar('6');   // quedan 4 en cuarentena

        $this->assertRechazo(CodigoError::AJUSTE_DEJA_NEGATIVO, $this->enviar('NEGATIVO', 'CUARENTENA', '5'));
        $this->assertRechazo(CodigoError::AJUSTE_DEJA_NEGATIVO, $this->enviar('NEGATIVO', 'LEGALIZADA', '7'));
        $this->assertRechazo(CodigoError::CANTIDAD_NO_ENTERA, $this->enviar('POSITIVO', 'CUARENTENA', '0,5'));

        $this->assertSinAjustes();
        $this->assertCantidades('10.000', '6.000');
    }

    public function test_unidades_reservadas_no_se_ajustan(): void
    {
        $this->legalizar('6');
        $linea = $this->linea->fresh();
        $linea->reservar('4');
        $linea->save();

        $this->assertRechazo(CodigoError::AJUSTE_AFECTA_RESERVADAS, $this->enviar('NEGATIVO', 'LEGALIZADA', '3'));
        $this->enviar('NEGATIVO', 'LEGALIZADA', '2')->assertSessionHasNoErrors();   // 6 − 2 = 4 = reservada

        $this->assertSame('4.000', $this->linea->fresh()->cantidad_legalizada);
    }

    public function test_linea_en_aforo_solo_admite_negativo_de_legalizadas(): void
    {
        $this->legalizar('6');
        $this->bulto->iniciarAforo();
        $this->bulto->save();

        $this->assertRechazo(CodigoError::AJUSTE_LINEA_EN_AFORO, $this->enviar('POSITIVO', 'CUARENTENA', '1'));
        $this->assertRechazo(CodigoError::AJUSTE_LINEA_EN_AFORO, $this->enviar('NEGATIVO', 'CUARENTENA', '1'));
        $this->assertSinAjustes();

        $this->enviar('NEGATIVO', 'LEGALIZADA', '1')->assertSessionHasNoErrors();   // J10
        $this->assertCantidades('9.000', '5.000');
    }

    // ------------------------------------------------------------ motivo, descripción, confirmación y soporte (J4, J6, J7, J9)

    public function test_correccion_de_registro_exige_un_movimiento_de_la_misma_linea(): void
    {
        $this->legalizar('6');
        $legalizacion = Movimiento::where('tipo', TipoMovimiento::LEGALIZACION->value)->where('cantidad', '>', 0)->sole();
        $otra = $this->bultoUbicado('5');
        $ajena = Movimiento::where('bulto_detalle_id', $otra->detalles()->value('id'))->firstOrFail();

        $this->enviar('NEGATIVO', 'LEGALIZADA', '1', ['motivo' => 'CORRECCION_REGISTRO'])->assertSessionHasErrors('movimiento_corregido_id');
        $this->enviar('NEGATIVO', 'LEGALIZADA', '1', ['motivo' => 'CORRECCION_REGISTRO', 'movimiento_corregido_id' => $ajena->id])
            ->assertSessionHasErrors(['movimiento_corregido_id' => CodigoError::AJUSTE_REFERENCIA_OTRA_LINEA->mensaje()]);
        $this->assertSinAjustes();

        $this->enviar('NEGATIVO', 'LEGALIZADA', '1', ['motivo' => 'CORRECCION_REGISTRO', 'movimiento_corregido_id' => $legalizacion->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($legalizacion->id, Ajuste::sole()->movimiento_corregido_id);
    }

    public function test_descripcion_y_confirmacion_son_obligatorias(): void
    {
        $this->enviar('POSITIVO', 'CUARENTENA', '1', ['descripcion' => '   '])->assertSessionHasErrors('descripcion');
        $this->enviar('POSITIVO', 'CUARENTENA', '1', ['confirmacion' => '0'])->assertSessionHasErrors('confirmacion');
        $this->enviar('POSITIVO', 'CUARENTENA', '1', ['motivo' => 'ROBO'])->assertSessionHasErrors('motivo');

        $this->assertSinAjustes();
    }

    public function test_el_soporte_es_opcional_y_privado(): void
    {
        $this->enviar('POSITIVO', 'CUARENTENA', '1')->assertSessionHasNoErrors();
        $sinSoporte = Ajuste::sole();
        $this->assertNull($sinSoporte->soporte_path);
        $this->get(route('ajustes.soporte', $sinSoporte))->assertNotFound();

        $this->enviar('POSITIVO', 'CUARENTENA', '1', ['soporte' => UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('soporte');

        $this->enviar('POSITIVO', 'CUARENTENA', '1', ['soporte' => UploadedFile::fake()->create('foto.jpg', 80, 'image/jpeg')])->assertSessionHasNoErrors();
        $conSoporte = Ajuste::latest('id')->first();

        Storage::disk('local')->assertExists($conSoporte->soporte_path);
        $this->get(route('ajustes.soporte', $conSoporte))->assertOk()->assertDownload("soporte-{$conSoporte->consecutivo}.jpg");
    }

    public function test_si_falla_la_regla_el_soporte_no_queda(): void
    {
        $this->assertRechazo(CodigoError::AJUSTE_DEJA_NEGATIVO, $this->enviar('NEGATIVO', 'CUARENTENA', '11', [
            'soporte' => UploadedFile::fake()->create('acta.pdf', 50, 'application/pdf'),
        ]));

        $this->assertSinAjustes();
    }

    // ------------------------------------------------------------ A2 / A3: aforo bloqueado → ajuste → cierre (ejemplo 10/6/4)

    public function test_flujo_aforo_bloqueado_ajuste_y_cierre_del_mismo_aforo(): void
    {
        $this->legalizar('6');

        $aforos = app(AforoService::class);
        $aforo = $aforos->activar($this->bl, [$this->bulto->id], $this->supervisor);
        $detalle = $aforo->detalles()->sole();
        $aforos->registrarConteo($aforo, $detalle->id, '4', $this->crearUsuario(Rol::OPERARIO));

        // 1. El cierre se bloquea (A2)
        $this->assertRechazo(CodigoError::CONTEO_MENOR_A_LEGALIZADO, $this->from(route('aforos.show', $aforo))->patch(route('aforos.cerrar', $aforo)));

        // 2. El aforo muestra el atajo prellenado (A3)
        $atajo = route('ajustes.create', [
            'bulto'       => $this->bulto->codigo,
            'linea'       => $this->linea->id,
            'tipo'        => 'NEGATIVO',
            'estado'      => 'LEGALIZADA',
            'cantidad'    => '2.000',
            'descripcion' => "AFO-{$aforo->id}: se contaron 4.000 y había 6.000 legalizadas (A2).",
            'aforo'       => $aforo->id,
        ]);
        $this->get(route('aforos.show', $aforo))->assertOk()->assertSee('Registrar ajuste')->assertSee($atajo);
        $this->get($atajo)->assertOk()->assertSee('AFO-'.$aforo->id);

        // 3. Ajuste −2 en LEGALIZADA: 8 recibidas y 4 legalizadas; vuelve al aforo
        $this->enviar('NEGATIVO', 'LEGALIZADA', '2', ['motivo' => 'FALTANTE', 'aforo_id' => $aforo->id])
            ->assertRedirect(route('aforos.show', $aforo));
        $this->assertCantidades('8.000', '4.000');

        // 4. Se cierra el MISMO aforo: AFORO −4 en cuarentena → 4 recibidas, 4 legalizadas, 0 en cuarentena
        $this->patch(route('aforos.cerrar', $aforo))->assertSessionHasNoErrors();

        $this->assertCantidades('4.000', '4.000');
        $this->assertSame('-4.000', Movimiento::where('tipo', TipoMovimiento::AFORO->value)->sole()->cantidad);
    }

    // ------------------------------------------------------------ pantallas e inmutabilidad

    public function test_pantalla_de_creacion_por_pasos(): void
    {
        $this->get(route('ajustes.create'))->assertOk();
        $this->get(route('ajustes.create', ['bulto' => 'BUL-000000-0000']))->assertOk()->assertSee('No existe un bulto');
        $this->get(route('ajustes.create', ['bulto' => strtolower($this->bulto->codigo)]))->assertOk()->assertSee($this->producto->sku)->assertDontSee('Revisar y confirmar');
        $this->get(route('ajustes.create', ['bulto' => $this->bulto->codigo, 'linea' => $this->linea->id]))->assertOk()->assertSee('Revisar y confirmar');
    }

    public function test_listado_detalle_y_sin_edicion(): void
    {
        $this->enviar('POSITIVO', 'CUARENTENA', '1');
        $ajuste = Ajuste::sole();

        $this->get(route('ajustes.index'))->assertOk()->assertSee($ajuste->consecutivo);
        $this->get(route('ajustes.index', ['motivo' => 'DANO']))->assertOk()->assertDontSee($ajuste->consecutivo);
        $this->get(route('ajustes.show', $ajuste))->assertOk()->assertSee($ajuste->consecutivo);

        $this->put(route('ajustes.show', $ajuste))->assertStatus(405);
        $this->delete(route('ajustes.show', $ajuste))->assertStatus(405);
        $this->assertModelExists($ajuste);
    }

    // ------------------------------------------------------------ permisos (J1, RS-54)

    #[DataProvider('otrosRoles')]
    public function test_solo_el_supervisor_ajusta(string $rol): void
    {
        $this->actingAs($this->crearUsuario(Rol::from($rol)));

        $this->get(route('ajustes.index'))->assertForbidden();
        $this->get(route('ajustes.create'))->assertForbidden();
        $this->enviar('POSITIVO', 'CUARENTENA', '1')->assertForbidden();

        $this->assertSinAjustes();
    }

    public static function otrosRoles(): array
    {
        return [['ANALISTA'], ['GERENTE'], ['OPERARIO'], ['ADMIN_MAESTROS'], ['ADMIN_SISTEMA']];
    }
}

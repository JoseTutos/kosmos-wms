<?php

namespace Tests\Feature\Web;

use App\Enums\EstadoLegal;
use App\Enums\Rol;
use App\Enums\TipoMovimiento;
use App\Models\Aforo;
use App\Models\Bl;
use App\Models\Bodega;
use App\Models\BultoDetalle;
use App\Models\Cliente;
use App\Models\Legalizacion;
use App\Models\Producto;
use App\Models\User;
use App\Services\Control\EstadoLegalService;
use App\Services\Control\HistoricoLegalizacionService;
use App\Services\Control\LegalizacionService;
use App\Services\Nucleo\MovimientoService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

/** CUS-011 Estado Legal Activo (C1–C4, RS-50) y CUS-012 Históricos y Auditoría (H1–H3, RS-51 a RS-53). */
final class EstadoLegalHistoricoTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    private User $analista;

    private Cliente $clienteA;

    private Cliente $clienteB;

    private Producto $productoA;

    private Producto $productoB;

    private Bodega $bodega;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->analista = $this->crearUsuario(Rol::ANALISTA);
        $this->clienteA = $this->crearCliente();
        $this->clienteB = $this->crearCliente();
        $this->productoA = $this->crearProducto($this->clienteA);
        $this->productoB = $this->crearProducto($this->clienteB);
        $this->bodega = $this->crearBodega();

        $this->actingAs($this->analista);
    }

    // ------------------------------------------------------------ escenario

    /** BL RECIBIDO hace $dias días, con un bulto ubicado y su RECEPCION. @return array{0: Bl, 1: BultoDetalle} */
    private function bl(Cliente $cliente, string $cantidad = '10', int $dias = 0): array
    {
        $producto = $cliente->is($this->clienteA) ? $this->productoA : $this->productoB;

        $bl = $this->crearBl($cliente, $producto);
        $bl->forceFill(['fecha_ingreso' => CarbonImmutable::now('America/Bogota')->subDays($dias)->toDateString()]);
        $bl->iniciarRecepcion();
        $bl->finalizarRecepcion(null);
        $bl->save();

        return [$bl, $this->bultoUbicado($bl, $producto, $cantidad)];
    }

    private function bultoUbicado(Bl $bl, Producto $producto, string $cantidad): BultoDetalle
    {
        $pallet = $this->crearPallet($bl);
        $bulto = $this->crearBulto($bl, $producto, $cantidad);
        $pallet->agregarBulto($bulto);
        $bulto->save();
        $pallet->cerrar();
        $pallet->ubicar($this->bodega);
        $pallet->save();

        $linea = $bulto->detalles()->firstOrFail();
        DB::transaction(fn () => app(MovimientoService::class)->registrar(
            TipoMovimiento::RECEPCION, $linea, $cantidad, $this->bodega->id, EstadoLegal::CUARENTENA, $this->analista, 'BL-PRUEBA',
        ));

        return $linea;
    }

    private function legalizar(Bl $bl, BultoDetalle $linea, string $cantidad, string $numero = 'RES-2026-001'): Legalizacion
    {
        return app(LegalizacionService::class)->registrar(
            $bl,
            [['modo' => 'B', 'linea_id' => $linea->id, 'cantidad' => $cantidad]],
            ['tipo_documento' => 'RESOLUCION_NACIONALIZACION', 'numero_autorizacion' => $numero, 'fecha_autorizacion' => '2026-11-20'],
            UploadedFile::fake()->create('soporte.pdf', 20, 'application/pdf'),
            $this->analista,
        );
    }

    /** @return list<string> */
    private function blsActivos(array $filtros = []): array
    {
        return app(EstadoLegalService::class)->consulta($filtros)->pluck('numero_bl')->all();
    }

    // ------------------------------------------------------------ CUS-011

    public function test_indicadores_dias_y_cuarentena_frente_al_total(): void
    {
        [$total] = $this->bl($this->clienteA, '10', 5);
        [$parcial, $lineaParcial] = $this->bl($this->clienteA, '10', 3);
        [$enAforo] = $this->bl($this->clienteB, '10', 1);
        $this->legalizar($parcial, $lineaParcial, '4');
        Aforo::create(['bl_id' => $enAforo->id]);

        $filas = app(EstadoLegalService::class)->consulta()->get()->keyBy('id');

        $this->assertSame(EstadoLegalService::INDICADOR_TOTAL, EstadoLegalService::indicador($filas[$total->id]));
        $this->assertSame(EstadoLegalService::INDICADOR_PARCIAL, EstadoLegalService::indicador($filas[$parcial->id]));
        $this->assertSame(EstadoLegalService::INDICADOR_AFORO, EstadoLegalService::indicador($filas[$enAforo->id]));
        $this->assertSame(5, (int) $filas[$total->id]->dias);
        $this->assertSame('6.000', $filas[$parcial->id]->total_cuarentena);
        $this->assertSame('10.000', $filas[$parcial->id]->total_recibida);

        // C3: más días primero
        $this->get(route('estado-legal.index'))
            ->assertOk()
            ->assertSeeInOrder([$total->numero_bl, $parcial->numero_bl, $enAforo->numero_bl])
            ->assertSee('Cuarentena total')
            ->assertSee('Cuarentena parcial')
            ->assertSee('En aforo');
    }

    public function test_el_bl_legalizado_al_100_sale_de_la_vista(): void
    {
        [$bl, $linea] = $this->bl($this->clienteA);
        $this->assertSame([$bl->numero_bl], $this->blsActivos());

        $this->legalizar($bl, $linea, '10');

        $this->assertSame([], $this->blsActivos());   // RS-50
        $this->get(route('estado-legal.index'))->assertOk()->assertDontSee($bl->numero_bl);
        $this->get(route('estado-legal.show', $bl))->assertOk()->assertSee('Todo legalizado');
    }

    public function test_filtros_por_cliente_bl_y_rango_de_dias(): void
    {
        [$viejo] = $this->bl($this->clienteA, '10', 30);
        [$nuevo] = $this->bl($this->clienteB, '10', 2);

        $this->assertSame([$viejo->numero_bl, $nuevo->numero_bl], $this->blsActivos());
        $this->assertSame([$viejo->numero_bl], $this->blsActivos(['cliente_id' => $this->clienteA->id]));
        $this->assertSame([$viejo->numero_bl], $this->blsActivos(['dias_min' => '10']));
        $this->assertSame([$nuevo->numero_bl], $this->blsActivos(['dias_max' => '5']));
        $this->assertSame([$nuevo->numero_bl], $this->blsActivos(['bl' => strtolower(substr($nuevo->numero_bl, 3, 5))]));

        $this->get(route('estado-legal.index', ['dias_min' => 10]))->assertOk()->assertSee($viejo->numero_bl)->assertDontSee($nuevo->numero_bl);
    }

    public function test_detalle_por_bulto_y_producto(): void
    {
        [$bl, $linea] = $this->bl($this->clienteA);
        $otra = $this->bultoUbicado($bl, $this->productoA, '5');
        $this->legalizar($bl, $linea, '4');

        $this->get(route('estado-legal.show', $bl))
            ->assertOk()
            ->assertSee($linea->bulto->codigo)
            ->assertSee($otra->bulto->codigo)
            ->assertSee($this->productoA->sku)
            ->assertSee('6.000')     // cuarentena de la primera línea
            ->assertSee('11.000')    // cuarentena total del BL (6 + 5)
            ->assertSee('Legalizar este BL');
    }

    // ------------------------------------------------------------ CUS-012

    /** @return array{0: Legalizacion, 1: Legalizacion} */
    private function dosLegalizaciones(): array
    {
        [$blA, $lineaA] = $this->bl($this->clienteA);
        [$blB, $lineaB] = $this->bl($this->clienteB);

        return [$this->legalizar($blA, $lineaA, '4', 'RES-AAA-01'), $this->legalizar($blB, $lineaB, '2', 'VB-BBB-02')];
    }

    public function test_historico_con_filtros_y_resumen(): void
    {
        [$a, $b] = $this->dosLegalizaciones();
        $historico = app(HistoricoLegalizacionService::class);
        $consecutivos = fn (array $f) => $historico->consulta($f)->pluck('consecutivo')->all();

        $this->assertSame([$b->consecutivo, $a->consecutivo], $consecutivos([]));   // más recientes primero
        $this->assertSame([$a->consecutivo], $consecutivos(['numero' => 'aaa']));
        $this->assertSame([$b->consecutivo], $consecutivos(['cliente_id' => $this->clienteB->id]));
        $this->assertSame([$a->consecutivo], $consecutivos(['bl' => $a->bl->numero_bl]));
        $this->assertSame([], $consecutivos(['desde' => now()->addDays(2)->toDateString()]));
        $this->assertSame([], $consecutivos(['hasta' => now()->subDays(2)->toDateString()]));

        $fila = $historico->consulta(['numero' => 'aaa'])->first();
        $this->assertSame(1, (int) $fila->bultos);
        $this->assertSame('4.000 UND', $fila->unidades);
        $this->assertSame(0, (int) $fila->seriales);

        $this->get(route('historicos.legalizaciones.index', ['numero' => 'AAA']))
            ->assertOk()
            ->assertSee($a->consecutivo)
            ->assertDontSee($b->consecutivo);
    }

    public function test_exporta_a_excel_con_los_filtros(): void
    {
        [$a] = $this->dosLegalizaciones();

        $respuesta = $this->get(route('historicos.legalizaciones.exportar', ['formato' => 'xlsx', 'numero' => 'AAA']));
        $respuesta->assertOk();
        $this->assertStringContainsString('spreadsheetml', $respuesta->headers->get('Content-Type'));

        $archivo = tempnam(sys_get_temp_dir(), 'leg').'.xlsx';
        file_put_contents($archivo, $respuesta->streamedContent());
        $filas = IOFactory::load($archivo)->getActiveSheet()->toArray();
        @unlink($archivo);

        $this->assertCount(2, $filas);                       // encabezado + 1 línea
        $this->assertSame('Legalización', $filas[0][1]);
        $this->assertSame($a->consecutivo, $filas[1][1]);
        $this->assertSame('RES-AAA-01', $filas[1][6]);
        $this->assertEquals(4, $filas[1][13]);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'legalizacion', 'description' => 'histórico exportado']);
    }

    public function test_exporta_a_csv_con_los_filtros(): void
    {
        [, $b] = $this->dosLegalizaciones();

        $respuesta = $this->get(route('historicos.legalizaciones.exportar', ['formato' => 'csv', 'cliente_id' => $this->clienteB->id]));
        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', $respuesta->headers->get('Content-Type'));

        $contenido = $respuesta->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);

        $lineas = array_values(array_filter(preg_split('/\R/', substr($contenido, 3))));
        $this->assertCount(2, $lineas);
        $fila = str_getcsv($lineas[1], ';', '"', '');
        $this->assertSame($b->consecutivo, $fila[1]);
        $this->assertSame('2.000', $fila[13]);

        $this->get(route('historicos.legalizaciones.exportar', ['formato' => 'pdf']))->assertNotFound();
    }

    public function test_el_gerente_consulta_el_detalle_y_el_soporte_sin_poder_legalizar(): void
    {
        [$a] = $this->dosLegalizaciones();
        $this->actingAs($this->crearUsuario(Rol::GERENTE));

        $this->get(route('historicos.legalizaciones.show', $a))->assertOk()->assertSee($a->consecutivo)->assertSee('Históricos');
        $this->get(route('historicos.legalizaciones.soporte', $a))->assertOk()->assertDownload("soporte-{$a->consecutivo}.pdf");
        $this->get(route('legalizaciones.show', $a))->assertForbidden();
        $this->get(route('legalizaciones.create'))->assertForbidden();

        // H3: nada se borra
        $this->delete(route('historicos.legalizaciones.show', $a))->assertStatus(405);
        $this->assertModelExists($a);
    }

    // ------------------------------------------------------------ permisos (C4, RS-53)

    #[DataProvider('rolesConAcceso')]
    public function test_consultan_supervisor_analista_y_gerente(string $rol): void
    {
        $this->actingAs($this->crearUsuario(Rol::from($rol)));

        $this->get(route('estado-legal.index'))->assertOk();
        $this->get(route('historicos.legalizaciones.index'))->assertOk();
        $this->get(route('historicos.legalizaciones.exportar', ['formato' => 'csv']))->assertOk();
    }

    public static function rolesConAcceso(): array
    {
        return [['SUPERVISOR'], ['ANALISTA'], ['GERENTE']];
    }

    #[DataProvider('rolesSinAcceso')]
    public function test_otros_roles_no_consultan(string $rol): void
    {
        $this->actingAs($this->crearUsuario(Rol::from($rol)));

        $this->get(route('estado-legal.index'))->assertForbidden();
        $this->get(route('historicos.legalizaciones.index'))->assertForbidden();
        $this->get(route('historicos.legalizaciones.exportar', ['formato' => 'xlsx']))->assertForbidden();
    }

    public static function rolesSinAcceso(): array
    {
        return [['OPERARIO'], ['ADMIN_MAESTROS'], ['ADMIN_SISTEMA']];
    }
}

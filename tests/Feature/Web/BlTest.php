<?php

namespace Tests\Feature\Web;

use App\Enums\EstadoBl;
use App\Enums\Rol;
use App\Models\Bl;
use App\Models\BlDetalle;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

/** CUS-005 Registrar BL (web, Supervisor). */
final class BlTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    private User $supervisor;

    private Cliente $cliente;

    private Producto $a;

    private Producto $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisor = $this->crearUsuario(Rol::SUPERVISOR);
        $this->cliente = $this->crearCliente();
        $this->a = $this->crearProducto($this->cliente);
        $this->b = $this->crearProducto($this->cliente);
        $this->actingAs($this->supervisor);
    }

    /** @param list<array<string, mixed>>|null $detalles */
    private function datos(array $cambios = [], ?array $detalles = null): array
    {
        return array_merge([
            'numero_bl'         => 'mscu 123 4567',
            'cliente_id'        => $this->cliente->id,
            'fecha_ingreso'     => '2026-11-30',
            'factura_comercial' => ' FC-2026-0815 ',
            'detalles'          => $detalles ?? [
                ['producto_id' => $this->a->id, 'bultos_esperados' => '10', 'unidades_esperadas' => '100', 'peso_bruto_kg' => '250,5'],
                ['producto_id' => $this->b->id, 'bultos_esperados' => '5', 'unidades_esperadas' => '12,750', 'peso_bruto_kg' => '80'],
            ],
        ], $cambios);
    }

    private function renglon(Producto $producto, string $unidades = '1', string $peso = '1'): array
    {
        return ['producto_id' => $producto->id, 'bultos_esperados' => '1', 'unidades_esperadas' => $unidades, 'peso_bruto_kg' => $peso];
    }

    // ------------------------------------------------------------ crear

    public function test_el_supervisor_registra_un_bl_con_su_manifiesto(): void
    {
        $this->post(route('bls.store'), $this->datos())->assertRedirect();

        $bl = Bl::with('detalles')->sole();

        $this->assertSame('MSCU1234567', $bl->numero_bl);              // B4: mayúsculas, sin espacios
        $this->assertSame('FC-2026-0815', $bl->factura_comercial);
        $this->assertSame(EstadoBl::PROGRAMADO, $bl->estado);
        $this->assertCount(2, $bl->detalles);
        $this->assertSame('250.500', $bl->detalles->firstWhere('producto_id', $this->a->id)->peso_bruto_kg);
        $this->assertSame('12.750', $bl->detalles->firstWhere('producto_id', $this->b->id)->unidades_esperadas);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'inbound', 'description' => 'BL registrado', 'subject_id' => $bl->id]);
    }

    public function test_las_filas_vacias_del_formulario_se_ignoran(): void
    {
        $detalles = [$this->renglon($this->a), ['producto_id' => '', 'bultos_esperados' => '', 'unidades_esperadas' => '', 'peso_bruto_kg' => '']];

        $this->post(route('bls.store'), $this->datos(detalles: $detalles))->assertSessionHasNoErrors();

        $this->assertSame(1, BlDetalle::count());
    }

    #[DataProvider('manifiestosInvalidos')]
    public function test_rechaza_manifiestos_invalidos(string $caso, string $campoConError): void
    {
        $detalles = match ($caso) {
            'otro_cliente' => [$this->renglon($this->crearProducto($this->crearCliente()))],
            'inactivo'     => [$this->renglon($this->productoInactivo())],
            'repetido'     => [$this->renglon($this->a), $this->renglon($this->a)],
            'unidades_0'   => [$this->renglon($this->a, unidades: '0')],
            'peso_vacio'   => [$this->renglon($this->a, peso: '')],
            'decimales'    => [$this->renglon($this->a, unidades: '1,2345')],
            'sin_detalle'  => [],
        };

        $this->post(route('bls.store'), $this->datos(detalles: $detalles))->assertSessionHasErrors($campoConError);

        $this->assertSame(0, Bl::count());
    }

    public static function manifiestosInvalidos(): array
    {
        return [
            'producto de otro cliente (B8)' => ['otro_cliente', 'detalles.0.producto_id'],
            'producto inactivo (B8)'        => ['inactivo', 'detalles.0.producto_id'],
            'producto repetido (B7)'        => ['repetido', 'detalles.1.producto_id'],
            'unidades en cero (B9)'         => ['unidades_0', 'detalles.0.unidades_esperadas'],
            'peso vacío (B9)'               => ['peso_vacio', 'detalles.0.peso_bruto_kg'],
            'más de 3 decimales'            => ['decimales', 'detalles.0.unidades_esperadas'],
            'sin productos'                 => ['sin_detalle', 'detalles'],
        ];
    }

    public function test_cliente_inactivo_se_rechaza(): void
    {
        $this->cliente->inactivar();
        $this->cliente->save();

        $this->post(route('bls.store'), $this->datos())->assertSessionHasErrors('cliente_id');
        $this->assertSame(0, Bl::count());
    }

    public function test_numero_de_bl_unico_y_sin_caracteres_raros(): void
    {
        $existente = $this->crearBl($this->cliente, $this->a);

        $this->post(route('bls.store'), $this->datos(['numero_bl' => strtolower($existente->numero_bl)]))->assertSessionHasErrors('numero_bl');
        $this->post(route('bls.store'), $this->datos(['numero_bl' => 'BL#2026']))->assertSessionHasErrors('numero_bl');
        $this->post(route('bls.store'), $this->datos(['numero_bl' => 'AB1']))->assertSessionHasErrors('numero_bl'); // mínimo 4

        $this->assertSame(1, Bl::count());
    }

    // ------------------------------------------------------------ editar y eliminar

    public function test_editar_actualiza_el_encabezado_y_sincroniza_el_manifiesto(): void
    {
        $bl = $this->crearBl($this->cliente, $this->a);
        $nuevo = $this->crearProducto($this->cliente);

        $this->put(route('bls.update', $bl), [
            'numero_bl'         => 'NUEVO-001',
            'fecha_ingreso'     => '2026-12-01',
            'factura_comercial' => 'FC-2',
            'detalles'          => [$this->renglon($this->b, '3'), $this->renglon($nuevo, '4')],
        ])->assertRedirect(route('bls.show', $bl));

        $bl->refresh();
        $this->assertSame('NUEVO-001', $bl->numero_bl);
        $this->assertEqualsCanonicalizing([$this->b->id, $nuevo->id], $bl->detalles()->pluck('producto_id')->all());
    }

    public function test_al_editar_el_numero_propio_no_cuenta_como_repetido(): void
    {
        $bl = $this->crearBl($this->cliente, $this->a);

        $this->put(route('bls.update', $bl), [
            'numero_bl' => $bl->numero_bl, 'fecha_ingreso' => '2026-12-01', 'factura_comercial' => 'FC-2',
            'detalles'  => [$this->renglon($this->a)],
        ])->assertSessionHasNoErrors();
    }

    public function test_fuera_de_programado_no_se_edita_ni_elimina(): void
    {
        $bl = $this->crearBl($this->cliente, $this->a);
        $bl->iniciarRecepcion();
        $bl->save();

        $this->get(route('bls.edit', $bl))->assertForbidden();
        $this->put(route('bls.update', $bl), $this->datos())->assertForbidden();
        $this->delete(route('bls.destroy', $bl))->assertForbidden();
        $this->get(route('bls.show', $bl))->assertOk();
    }

    public function test_eliminar_borra_el_bl_y_su_manifiesto(): void
    {
        $bl = $this->crearBl($this->cliente, $this->a, $this->b);

        $this->delete(route('bls.destroy', $bl))->assertRedirect(route('bls.index'));

        $this->assertModelMissing($bl);
        $this->assertSame(0, BlDetalle::count());
        $this->assertDatabaseHas('activity_log', ['log_name' => 'inbound', 'description' => 'BL eliminado']);
    }

    public function test_no_se_elimina_un_bl_con_bultos(): void
    {
        $bl = $this->crearBl($this->cliente, $this->a);
        $this->crearBulto($bl, $this->a);

        $this->delete(route('bls.destroy', $bl))->assertForbidden();
        $this->assertModelExists($bl);
    }

    // ------------------------------------------------------------ preinspección (B6)

    public function test_acta_de_preinspeccion_se_carga_descarga_y_quita(): void
    {
        Storage::fake('local');

        $this->post(route('bls.store'), $this->datos([
            'fecha_preinspeccion' => now()->toDateString(),
            'acta'                => UploadedFile::fake()->create('acta.pdf', 120, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        $bl = Bl::sole();
        $ruta = $bl->acta_preinspeccion_path;
        Storage::disk('local')->assertExists($ruta);
        $this->get(route('bls.acta', $bl))->assertOk()->assertDownload();

        // En recepción todavía se puede cambiar (B6)
        $bl->iniciarRecepcion();
        $bl->save();
        $this->patch(route('bls.preinspeccion', $bl), ['fecha_preinspeccion' => now()->toDateString(), 'quitar_acta' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertNull($bl->refresh()->acta_preinspeccion_path);
        Storage::disk('local')->assertMissing($ruta);

        // Recibido: ya no
        $bl->finalizarRecepcion(null);
        $bl->save();
        $this->patch(route('bls.preinspeccion', $bl), ['fecha_preinspeccion' => now()->toDateString()])->assertForbidden();
    }

    public function test_el_acta_solo_admite_pdf_o_imagen(): void
    {
        Storage::fake('local');

        $this->post(route('bls.store'), $this->datos(['acta' => UploadedFile::fake()->create('acta.exe', 10)]))
            ->assertSessionHasErrors('acta');
    }

    // ------------------------------------------------------------ permisos (B1)

    #[DataProvider('otrosRoles')]
    public function test_solo_el_supervisor_gestiona_bl(string $rol): void
    {
        $this->actingAs($this->crearUsuario(Rol::from($rol)));

        $this->get(route('bls.index'))->assertForbidden();
        $this->post(route('bls.store'), $this->datos())->assertForbidden();
        $this->assertSame(0, Bl::count());
    }

    public static function otrosRoles(): array
    {
        return [['ANALISTA'], ['ADMIN_MAESTROS'], ['ADMIN_SISTEMA'], ['GERENTE']];
    }

    private function productoInactivo(): Producto
    {
        $producto = $this->crearProducto($this->cliente);
        $producto->inactivar();
        $producto->save();

        return $producto;
    }
}

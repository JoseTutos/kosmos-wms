<?php

namespace Tests\Feature\Vistas;

use App\Enums\Rol;
use App\Models\Aforo;
use App\Models\Bl;
use App\Models\BlDetalle;
use App\Models\Bodega;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Ubicacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;
use App\Models\Legalizacion;
use App\Models\Ajuste;
use App\Models\Bulto;
/**
 * L5 – Prueba de humo: cada pantalla web actual responde 200 con el rol correcto.
 * Atrapa los errores de Blade (L1, L2) antes de abrir la pantalla en el navegador.
 * Al agregar una pantalla nueva (BL, aforos...), agréguela a vistas().
 */
final class RenderizadoVistasTest extends TestCase
{
    use CreaUsuarios, RefreshDatabase;

    /** @return array<string, array{string, string, ?string}>  [rol, ruta, modelo del parámetro] */
    public static function vistas(): array
    {
        return [
            // Módulo 1 · Seguridad (CUS-004)
            'usuarios.index'     => ['ADMIN_SISTEMA', 'usuarios.index', null],
            'usuarios.create'    => ['ADMIN_SISTEMA', 'usuarios.create', null],
            'usuarios.edit'      => ['ADMIN_SISTEMA', 'usuarios.edit', 'usuario'],

            // Módulo 1 · Maestros (CUS-001 a 003)
            'clientes.index'     => ['ADMIN_MAESTROS', 'clientes.index', null],
            'clientes.create'    => ['ADMIN_MAESTROS', 'clientes.create', null],
            'clientes.edit'      => ['ADMIN_MAESTROS', 'clientes.edit', 'cliente'],
            'productos.index'    => ['ADMIN_MAESTROS', 'productos.index', null],
            'productos.create'   => ['ADMIN_MAESTROS', 'productos.create', null],
            'productos.edit'     => ['ADMIN_MAESTROS', 'productos.edit', 'producto'],
            'productos.importar' => ['ADMIN_MAESTROS', 'productos.importar', null],
            'bodegas.index'      => ['ADMIN_MAESTROS', 'bodegas.index', null],
            'bodegas.create'     => ['ADMIN_MAESTROS', 'bodegas.create', null],
            'bodegas.show'       => ['ADMIN_MAESTROS', 'bodegas.show', 'bodega'],
            'bodegas.edit'       => ['ADMIN_MAESTROS', 'bodegas.edit', 'bodega'],

            // Módulo 2 · Entrada (CUS-005)
            'bls.index'          => ['SUPERVISOR', 'bls.index', null],
            'bls.create'         => ['SUPERVISOR', 'bls.create', null],
            'bls.show'           => ['SUPERVISOR', 'bls.show', 'bl'],
            'bls.edit'           => ['SUPERVISOR', 'bls.edit', 'bl'],

            // Módulo 2 · Aforo (CUS-009)
            'aforos.index'       => ['SUPERVISOR', 'aforos.index', null],
            'aforos.create'      => ['SUPERVISOR', 'aforos.create', null],
            'aforos.show'        => ['SUPERVISOR', 'aforos.show', 'aforo'],

            // Módulo 2 · Etiquetas (CUS-007)
            'etiquetas.index'    => ['SUPERVISOR', 'etiquetas.index', null],

             // Módulo 3 · Legalización (CUS-010)
            'legalizaciones.index'  => ['ANALISTA', 'legalizaciones.index', null],
            'legalizaciones.create' => ['ANALISTA', 'legalizaciones.create', null],
            'legalizaciones.show'   => ['ANALISTA', 'legalizaciones.show', 'legalizacion'],

                        // Módulo 3 · Ajustes (CUS-019)
            'ajustes.index'         => ['SUPERVISOR', 'ajustes.index', null],
            'ajustes.create'        => ['SUPERVISOR', 'ajustes.create', null],
            'ajustes.show'          => ['SUPERVISOR', 'ajustes.show', 'ajuste'],
            
            // Módulo 3 · Estado legal (CUS-011) e Históricos (CUS-012)
            'estado-legal.index'               => ['GERENTE', 'estado-legal.index', null],
            'estado-legal.show'                => ['GERENTE', 'estado-legal.show', 'bl'],
            'historicos.legalizaciones.index'  => ['GERENTE', 'historicos.legalizaciones.index', null],
            'historicos.legalizaciones.show'   => ['GERENTE', 'historicos.legalizaciones.show', 'legalizacion'],
        ];

        
    }

    /**
     * Pantallas con ruta pero sin vista todavía. Se reportan como "skipped" (no como falla).
     * Al construir la vista, quite su línea de aquí y la prueba la empieza a exigir.
     */
    private const PENDIENTES = [];

    private function saltarSiPendiente(string $clave): void
    {
        if (isset(self::PENDIENTES[$clave])) {
            $this->markTestSkipped('Vista pendiente: '.self::PENDIENTES[$clave]);
        }
    }

    #[DataProvider('vistas')]
    public function test_vista_con_datos(string $rol, string $ruta, ?string $parametro): void
    {
        $this->saltarSiPendiente($ruta);

        $datos = $this->crearDatos();

        $this->actingAs($this->crearUsuario(Rol::from($rol)))
            ->get(route($ruta, $parametro ? [$datos[$parametro]] : []))
            ->assertOk();
    }

    /** Listados sin registros: renderiza la rama @empty / @forelse. */
    #[DataProvider('listados')]
    public function test_listado_vacio(string $ruta): void
    {
        $this->actingAs($this->crearUsuario(Rol::ADMIN_MAESTROS))
            ->get(route($ruta))
            ->assertOk();
    }

    public static function listados(): array
    {
        return [['clientes.index'], ['productos.index'], ['bodegas.index']];
    }

    public function test_listado_de_usuarios_con_avisos_y_clave_temporal(): void
    {
        $this->saltarSiPendiente('modal_clave_temporal');

        $this->crearDatos();

        $this->actingAs($this->crearUsuario(Rol::ADMIN_SISTEMA))
            ->withSession(['ok' => 'Usuario actualizado.', 'password_temporal' => [
                'clave'   => config('kosmos.password_por_defecto'),
                'usuario' => 'Ana Pérez · ana@kosmos.test',
                'titulo'  => 'Contraseña restablecida',
            ]])
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('Contraseña restablecida')
            ->assertSee(config('kosmos.password_por_defecto'));
    }

    public function test_login_se_renderiza(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_cambio_de_clave_se_renderiza(): void
    {
        $this->actingAs($this->crearUsuario(Rol::ANALISTA, ['debe_cambiar_password' => true]))
            ->get(route('perfil.password.edit'))
            ->assertOk();
    }

    #[DataProvider('rolesWeb')]
    public function test_dashboard_por_rol(string $rol): void
    {
        $this->actingAs($this->crearUsuario(Rol::from($rol)))
            ->get(route('dashboard'))
            ->assertOk();
    }

    public static function rolesWeb(): array
    {
        return [['ADMIN_SISTEMA'], ['ADMIN_MAESTROS'], ['SUPERVISOR'], ['ANALISTA'], ['GERENTE']];
    }

    /** L1 – La forma en línea @php(...) está prohibida en todo el proyecto. */
    public function test_ninguna_vista_usa_php_en_linea(): void
    {
        $infractores = [];

        foreach (File::allFiles(resource_path('views')) as $archivo) {
            if (! str_ends_with($archivo->getFilename(), '.blade.php')) {
                continue;
            }

            // Los comentarios Blade {{-- --}} no se revisan (pueden documentar la regla); se conservan sus saltos de línea.
            $codigo = preg_replace_callback('/\{\{--.*?--\}\}/s', fn ($m) => str_repeat("\n", substr_count($m[0], "\n")), $archivo->getContents());

            foreach (preg_split('/\R/', $codigo) as $i => $linea) {
                if (preg_match('/(?<!@)@php\s*\(/', $linea)) {
                    $infractores[] = $archivo->getRelativePathname().':'.($i + 1);
                }
            }
        }

        $this->assertSame([], $infractores, 'Use @php ... @endphp (L1). Encontrado en: '.implode(', ', $infractores));
    }

    /**
     * Un registro de cada maestro, con todas sus columnas, para que las vistas pinten filas reales.
     * Si una columna del repositorio se llama distinto, ajústela solo aquí.
     *
     * @return array<string, Model>
     */
    private function crearDatos(): array
    {
        $cliente = Cliente::forceCreate([
            'nit'             => '900123456',
            'codigo_corto'    => 'ACME',
            'razon_social'    => 'Acme Importaciones S.A.S.',
            'direccion'       => 'Zona Franca La Candelaria, Bodega 4',
            'telefono'        => '6056601234',
            'contacto_nombre' => 'Ana Pérez',
            'estado'          => 'ACTIVO',
        ]);

        $producto = Producto::forceCreate([
            'cliente_id'          => $cliente->id,
            'sku'                 => 'ACME-00001',
            'descripcion'         => 'Producto de prueba',
            'tipo_almacenamiento' => 'PALETIZADO',
            'unidad_medida'       => 'UND',
            'peso_unitario_kg'    => 1.5,
            'stock_minimo'        => 0,
            'estado'              => 'ACTIVO',
        ]);

        $bodega = Bodega::forceCreate([
            'codigo'             => 'BOD-01',
            'nombre'             => 'Bodega principal',
            'tamano_m2'          => 500,
            'maneja_ubicaciones' => true,
            'estado'             => 'ACTIVA',
            'm2_ocupados'        => 0,
        ]);

        Ubicacion::forceCreate([
            'bodega_id' => $bodega->id,
            'codigo'    => 'A-01-01',
            'estado'    => 'ACTIVA',
        ]);

        $bl = Bl::create([
            'numero_bl'         => 'MSCU1234567',
            'cliente_id'        => $cliente->id,
            'fecha_ingreso'     => now()->toDateString(),
            'factura_comercial' => 'FC-2026-0001',
        ]);
        BlDetalle::create([
            'bl_id' => $bl->id, 'producto_id' => $producto->id,
            'bultos_esperados' => 10, 'unidades_esperadas' => '100', 'peso_bruto_kg' => '250.5',
        ]);

        $aforo = Aforo::create(['bl_id' => $bl->id]);

         $legalizacion = Legalizacion::create([
            'consecutivo'         => 'LEG-2026-00001',
            'bl_id'               => $bl->id,
            'tipo_documento'      => 'VOBO',
            'numero_autorizacion' => 'VB-2026-0001',
            'fecha_autorizacion'  => now()->toDateString(),
            'soporte_path'        => 'legalizaciones/2026/soporte.pdf',
            'usuario_id'          => $this->crearUsuario(Rol::ANALISTA)->id,
        ]);

        $bulto = Bulto::create(['codigo' => 'BUL-251126-0001', 'bl_id' => $bl->id, 'peso_bruto_kg' => '25.000'])->refresh();
        $linea = $bulto->agregarLinea($producto, '10');
        $linea->save();

        $ajuste = Ajuste::create([
            'consecutivo'      => 'AJ-2026-00001',
            'bulto_detalle_id' => $linea->id,
            'tipo'             => 'POSITIVO',
            'estado_afectado'  => 'CUARENTENA',
            'cantidad'         => '1',
            'motivo'           => 'SOBRANTE',
            'descripcion'      => 'Sobrante encontrado en el recorrido.',
            'usuario_id'       => $this->crearUsuario(Rol::SUPERVISOR)->id,
        ]);

        return [
            'usuario'  => $this->crearUsuario(Rol::OPERARIO),
            'cliente'  => $cliente,
            'producto' => $producto,
            'bodega'   => $bodega,
            'bl'       => $bl,
            'aforo'    => $aforo,
            'legalizacion' => $legalizacion,
            'ajuste'       => $ajuste,
        ];
    }
}

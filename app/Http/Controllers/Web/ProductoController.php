<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoActivo;
use App\Enums\TipoAlmacenamiento;
use App\Enums\UnidadMedida;
use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Producto\ImportarProductosRequest;
use App\Http\Requests\Producto\StoreProductoRequest;
use App\Http\Requests\Producto\UpdateProductoRequest;
use App\Models\Cliente;
use App\Models\Producto;
use App\Services\Maestros\ProductoImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kosmos WMS · Capa de Presentación (Controlador Web) · CUS-002 Gestionar Productos
 *
 * Actor: ADMIN_MAESTROS (middleware 'role:ADMIN_MAESTROS'; RE-7).
 *
 * Reparto (An. §3.3):
 *   - Modelo Producto: cliente ACTIVO, SKU automático {CLIENTE}-{00000} (RS-07),
 *     SKU y cliente inmutables, sin borrado (RS-04).
 *   - ProductoImportService: importación CSV todo o nada.
 *   - Este controlador: HTTP ↔ modelo/servicio, sin reglas de negocio propias salvo
 *     la protección de la unidad de medida (abajo).
 *
 * Cumple:
 *   - CUS-002: CRUD sin borrado, inactivación, importación masiva y plantilla CSV.
 *   - RS-73: el stock mínimo se guarda aquí; el indicador visual vive en Inventario (CUS-015).
 *
 * Rendimiento: listado paginado con el cliente precargado (with), sin N+1.
 */
class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $texto = $request->string('q')->trim()->substr(0, 100)->value();

        $productos = Producto::query()
            ->with('cliente:id,codigo_corto,razon_social')
            ->when($texto, fn ($q) => $q->where(fn ($w) => $w
                ->where('sku', 'ilike', "%{$texto}%")
                ->orWhere('descripcion', 'ilike', "%{$texto}%")))
            ->when($request->cliente_id, fn ($q, $id) => $q->where('cliente_id', $id))
            ->when($request->tipo, fn ($q, $tipo) => $q->where('tipo_almacenamiento', $tipo))
            ->when($request->estado, fn ($q, $estado) => $q->where('estado', $estado))
            ->orderBy('sku')
            ->paginate(20)
            ->withQueryString();

        return view('productos.index', [
            'productos' => $productos,
            'clientes'  => Cliente::orderBy('razon_social')->get(['id', 'codigo_corto', 'razon_social']),
            'tipos'     => TipoAlmacenamiento::cases(),
            'estados'   => EstadoActivo::cases(),
        ]);
    }

    public function create(): View
    {
        return view('productos.create', $this->opcionesFormulario());
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        // Transacción: el SKU (consecutivo) y el producto se guardan juntos o ninguno.
        $producto = DB::transaction(fn () => Producto::create($request->validated()));

        return redirect()->route('productos.index')->with('ok', "Producto {$producto->sku} creado.");
    }

    public function edit(Producto $producto): View
    {
        $producto->load('cliente:id,codigo_corto,razon_social');

        return view('productos.edit', ['producto' => $producto] + $this->opcionesFormulario());
    }

    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        $datos = $request->validated();

        // Integridad: con mercancía registrada, cambiar UND ↔ KG ↔ LT alteraría el sentido
        // de todas las cantidades ya guardadas (bultos, existencias, kardex).
        if ($datos['unidad_medida'] !== $producto->unidad_medida->value && $producto->bultoDetalles()->exists()) {
            throw new ReglaNegocioException('No se puede cambiar la unidad de medida: el producto ya tiene mercancía registrada.');
        }

        $producto->update($datos);

        return redirect()->route('productos.index')->with('ok', "Producto {$producto->sku} actualizado.");
    }

    /** RS-04: se inactiva, no se borra. Un producto INACTIVO no entra en BL nuevos. */
    public function inactivar(Producto $producto): RedirectResponse
    {
        $producto->inactivar();
        $producto->save();

        return back()->with('ok', "Producto {$producto->sku} inactivado.");
    }

    public function activar(Producto $producto): RedirectResponse
    {
        if (! $producto->cliente->estaActivo()) {
            throw new ReglaNegocioException("No se puede reactivar: el cliente {$producto->cliente->codigo_corto} está inactivo.");
        }

        $producto->activar();
        $producto->save();

        return back()->with('ok', "Producto {$producto->sku} reactivado.");
    }

    // ---------------- Importación CSV ----------------

    public function importarForm(): View
    {
        return view('productos.importar', [
            'clientes' => Cliente::where('estado', EstadoActivo::ACTIVO)->orderBy('razon_social')->get(['id', 'codigo_corto', 'razon_social']),
            'maximo'   => ProductoImportService::MAX_FILAS,
        ]);
    }

    public function importar(ImportarProductosRequest $request, ProductoImportService $servicio): RedirectResponse
    {
        $cliente   = Cliente::findOrFail($request->integer('cliente_id'));
        $resultado = $servicio->importar($cliente, $request->file('archivo'));

        if ($resultado['errores']) {
            return back()->withInput()->with('errores_importacion', $resultado['errores']);
        }

        return redirect()->route('productos.index', ['cliente_id' => $cliente->id])
            ->with('ok', "Se importaron {$resultado['importados']} productos para {$cliente->codigo_corto}.");
    }

    /** Plantilla con el encabezado exacto y una fila de ejemplo. Separador ';' para Excel en español. */
    public function plantilla(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo "\xEF\xBB\xBF"; // BOM: Excel abre bien las tildes
            echo "descripcion;tipo_almacenamiento;unidad_medida;peso_unitario_kg;stock_minimo\n";
            echo "Tornillo hexagonal M8 galvanizado;PALETIZADO;UND;0,015;100\n";
        }, 'plantilla-productos.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function opcionesFormulario(): array
    {
        return [
            'clientes' => Cliente::where('estado', EstadoActivo::ACTIVO)->orderBy('razon_social')->get(['id', 'codigo_corto', 'razon_social']),
            'tipos'    => TipoAlmacenamiento::cases(),
            'unidades' => UnidadMedida::cases(),
        ];
    }
}

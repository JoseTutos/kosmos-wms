<?php

namespace App\Http\Controllers\Web;

use App\Enums\EstadoLegal;
use App\Enums\MotivoAjuste;
use App\Enums\TipoAjuste;
use App\Enums\TipoMovimiento;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreAjusteRequest;
use App\Models\Aforo;
use App\Models\AforoDetalle;
use App\Models\Ajuste;
use App\Models\Bulto;
use App\Models\BultoDetalle;
use App\Models\Movimiento;
use App\Services\Control\AjusteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CUS-019 Registrar Ajuste de Inventario (Supervisor). Controlador delgado: valida, autoriza y delega. */
class AjusteController extends Controller
{
    public function __construct(private readonly AjusteService $ajustes) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Ajuste::class);

        $texto = $request->string('q')->trim()->substr(0, 50)->value();
        $motivo = MotivoAjuste::tryFrom((string) $request->query('motivo'));
        $tipo = TipoAjuste::tryFrom((string) $request->query('tipo'));
        $estado = EstadoLegal::tryFrom((string) $request->query('estado'));
        $desde = $request->date('desde');
        $hasta = $request->date('hasta');

        $ajustes = Ajuste::query()
            ->with([
                'bultoDetalle:id,bulto_id,producto_id,bl_id',
                'bultoDetalle.bulto:id,codigo',
                'bultoDetalle.producto:id,sku,unidad_medida',
                'bultoDetalle.bl:id,numero_bl',
                'usuario:id,nombre',
            ])
            ->when($texto, fn ($q) => $q->where(fn ($w) => $w
                ->where('consecutivo', 'ilike', "%{$texto}%")
                ->orWhereHas('bultoDetalle.bulto', fn ($b) => $b->where('codigo', 'ilike', "%{$texto}%"))
                ->orWhereHas('bultoDetalle.bl', fn ($b) => $b->where('numero_bl', 'ilike', "%{$texto}%"))
                ->orWhereHas('bultoDetalle.producto', fn ($p) => $p->where('sku', 'ilike', "%{$texto}%"))))
            ->when($motivo, fn ($q) => $q->where('motivo', $motivo->value))
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo->value))
            ->when($estado, fn ($q) => $q->where('estado_afectado', $estado->value))
            ->when($desde, fn ($q) => $q->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('ajustes.index', [
            'ajustes' => $ajustes,
            'motivos' => MotivoAjuste::opciones(),
            'tipos'   => TipoAjuste::opciones(),
            'estados' => EstadoLegal::opciones(),
        ]);
    }

    /**
     * Buscar o escanear el bulto (?bulto=) → elegir la línea (?linea=) → formulario con vista previa (J9).
     * El atajo del aforo (A3) llega con tipo, estado, cantidad, descripción y aforo prellenados.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', Ajuste::class);

        $codigo = Str::upper(trim((string) $request->query('bulto', '')));

        $bulto = $codigo === '' ? null : Bulto::query()
            ->where('codigo', $codigo)
            ->with([
                'bl:id,numero_bl,cliente_id',
                'bl.cliente:id,codigo_corto',
                'pallet:id,codigo,estado,bodega_id,ubicacion_id',
                'pallet.bodega:id,codigo',
                'detalles' => fn ($q) => $q->orderBy('id'),
                'detalles.producto:id,sku,descripcion,unidad_medida',
            ])
            ->first();

        $linea = $bulto && $request->filled('linea') ? $bulto->detalles->firstWhere('id', $request->integer('linea')) : null;

        return view('ajustes.create', [
            'codigo'      => $codigo,
            'bulto'       => $bulto,
            'linea'       => $linea,
            'movimientos' => $linea ? Movimiento::where('bulto_detalle_id', $linea->id)->orderByDesc('id')->limit(50)->get() : collect(),
            'aforo'       => $request->filled('aforo') ? Aforo::find($request->integer('aforo')) : null,
            'previo'      => $request->only(['tipo', 'estado', 'cantidad', 'descripcion']),
            'tipos'       => TipoAjuste::opciones(),
            'estados'     => EstadoLegal::opciones(),
            'motivos'     => MotivoAjuste::opciones(),
        ]);
    }

    public function store(StoreAjusteRequest $request): RedirectResponse
    {
        $linea = BultoDetalle::findOrFail($request->integer('bulto_detalle_id'));

        $ajuste = $this->ajustes->registrar(
            $linea,
            TipoAjuste::from($request->validated('tipo')),
            (string) $request->validated('cantidad'),
            EstadoLegal::from($request->validated('estado_afectado')),
            MotivoAjuste::from($request->validated('motivo')),
            $request->validated('descripcion'),
            $request->movimientoCorregidoId(),
            $request->file('soporte'),
            $request->user(),
        );

        $mensaje = "Ajuste {$ajuste->consecutivo} registrado.";
        $aforoId = $request->integer('aforo_id');

        // A3: si vino del aforo, vuelve al mismo aforo para cerrarlo
        if ($aforoId && AforoDetalle::where('aforo_id', $aforoId)->where('bulto_detalle_id', $linea->id)->exists()) {
            return redirect()->route('aforos.show', $aforoId)->with('ok', "{$mensaje} Ya puede intentar cerrar el aforo AFO-{$aforoId}.");
        }

        return redirect()->route('ajustes.show', $ajuste)->with('ok', $mensaje);
    }

    public function show(Ajuste $ajuste): View
    {
        Gate::authorize('view', $ajuste);

        $ajuste->load([
            'usuario:id,nombre',
            'movimientoCorregido',
            'bultoDetalle.producto:id,sku,descripcion,unidad_medida',
            'bultoDetalle.bl:id,numero_bl,cliente_id',
            'bultoDetalle.bl.cliente:id,codigo_corto,razon_social',
            'bultoDetalle.bulto:id,codigo,pallet_id',
            'bultoDetalle.bulto.pallet:id,codigo,bodega_id',
            'bultoDetalle.bulto.pallet.bodega:id,codigo',
        ]);

        return view('ajustes.show', [
            'ajuste'     => $ajuste,
            'movimiento' => Movimiento::where('tipo', TipoMovimiento::AJUSTE->value)->where('documento_ref', $ajuste->consecutivo)->first(),
        ]);
    }

    /** Descarga privada del soporte (J7). */
    public function soporte(Ajuste $ajuste): StreamedResponse
    {
        Gate::authorize('view', $ajuste);

        $disco = Storage::disk('local');
        abort_unless($ajuste->soporte_path && $disco->exists($ajuste->soporte_path), 404, 'El ajuste no tiene soporte.');

        $extension = pathinfo($ajuste->soporte_path, PATHINFO_EXTENSION);

        return $disco->download($ajuste->soporte_path, "soporte-{$ajuste->consecutivo}.{$extension}");
    }
}

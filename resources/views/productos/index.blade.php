{{--
    Kosmos WMS · Vista · CUS-002 Productos — ProductoController@index
    Actor: ADMIN_MAESTROS.

    Cumple:
      - CUS-002: consulta con filtros por cliente, tipo y estado; alta, edición, importación CSV,
        inactivación y reactivación. Sin borrar (RS-04). SKU automático y no editable (RS-07).
      - Confirmación según el riesgo: Reactivar es directo; Inactivar abre la ventana modal.

    Seguridad: cambios por POST con @csrf/@method; datos a JS con Js::from.
    Rendimiento: paginado de 20 con el cliente precargado (sin N+1).
--}}
@extends('layouts.app')

@use('App\Support\Etiquetas')
@use('Illuminate\Support\Js')

@section('titulo', 'Productos')
@section('migas')
    Maestros <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Productos</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Productos</h1>
            <p class="mt-1 text-sm text-slate-500">El SKU se genera solo con el código del cliente y no se puede cambiar.</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('productos.importar') }}"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
                Importar CSV
            </a>
            <a href="{{ route('productos.create') }}"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
                <x-icono nombre="mas" class="size-[18px]" /> Nuevo producto
            </a>
        </div>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <form method="GET" action="{{ route('productos.index') }}" role="search" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4">
            <div class="flex min-w-56 flex-1 flex-col gap-1.5">
                <label for="q" class="text-xs font-medium text-slate-500">Buscar</label>
                <input id="q" name="q" type="search" value="{{ request('q') }}" maxlength="100" placeholder="SKU o descripción"
                       class="h-10 rounded-[10px] border border-slate-300 px-3 text-sm outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="cliente_id" class="text-xs font-medium text-slate-500">Cliente</label>
                <select id="cliente_id" name="cliente_id" class="h-10 max-w-56 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    @foreach ($clientes as $cl)
                        <option value="{{ $cl->id }}" @selected((string) request('cliente_id') === (string) $cl->id)>{{ $cl->codigo_corto }} · {{ $cl->razon_social }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="tipo" class="text-xs font-medium text-slate-500">Tipo</label>
                <select id="tipo" name="tipo" class="h-10 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->value }}" @selected(request('tipo') === $tipo->value)>{{ Etiquetas::tipoAlmacenamiento($tipo) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="estado" class="text-xs font-medium text-slate-500">Estado</label>
                <select id="estado" name="estado" class="h-10 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>{{ Etiquetas::activo($estado) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="h-10 rounded-[10px] bg-slate-800 px-4 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
            @if (request()->hasAny(['q', 'cliente_id', 'tipo', 'estado']))
                <a href="{{ route('productos.index') }}" class="h-10 px-2 text-sm leading-10 text-marca-600 hover:underline">Limpiar</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">SKU</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Descripción</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Cliente</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Tipo</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Unidad</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Peso (kg)</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Stock mín.</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($productos as $p)
                    @php
                        $activo = $p->estaActivo();
                    @endphp
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-mono text-[13px] font-semibold">{{ $p->sku }}</td>
                        <td @class(['px-4 py-3', 'text-slate-500' => ! $activo])>{{ $p->descripcion }}</td>
                        <td class="px-4 py-3" title="{{ $p->cliente->razon_social }}">{{ $p->cliente->codigo_corto }}</td>
                        <td class="px-4 py-3">{{ Etiquetas::tipoAlmacenamiento($p->tipo_almacenamiento) }}</td>
                        <td class="px-4 py-3">{{ $p->unidad_medida->value }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $p->peso_unitario_kg }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $p->stock_minimo }}</td>
                        <td class="px-4 py-3"><x-estado :activo="$activo" :texto="Etiquetas::activo($p->estado)" /></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('productos.edit', $p) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Editar</a>
                                @if ($activo)
                                    @php
                                        $accion = [
                                            'url' => route('productos.inactivar', $p), 'metodo' => 'PATCH', 'peligro' => true,
                                            'titulo' => '¿Inactivar el producto '.$p->sku.'?',
                                            'detalle' => $p->descripcion,
                                            'puntos' => ['No se podrá incluir en BL nuevos.', 'Lo que ya está en bodega sigue su trámite normal.', 'Podrá reactivarlo cuando quiera.'],
                                            'boton' => 'Inactivar producto',
                                        ];
                                    @endphp
                                    <button type="button" @click="$dispatch('confirmar', {{ Js::from($accion) }})"
                                            class="h-9 rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Inactivar</button>
                                @else
                                    <form method="POST" action="{{ route('productos.activar', $p) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="h-9 rounded-lg border border-green-700 px-3 text-[13px] font-medium text-green-800 hover:bg-green-50">Reactivar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">
                            @if (request()->hasAny(['q', 'cliente_id', 'tipo', 'estado']))
                                No hay productos con esos filtros.
                            @else
                                Cree un producto o importe varios desde un archivo CSV.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($productos->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $productos->links() }}</div>
        @endif
    </div>
@endsection

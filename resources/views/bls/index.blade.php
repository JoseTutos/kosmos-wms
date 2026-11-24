@extends('layouts.app')

@section('titulo', 'BL')

@section('migas')
    Entrada <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">BL</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">BL</h1>
            <p class="mt-1 text-sm text-slate-500">Documentos de transporte con su manifiesto. Solo se editan mientras están programados.</p>
        </div>
        @can('create', App\Models\Bl::class)
            <a href="{{ route('bls.create') }}"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
                <x-icono nombre="mas" class="size-[18px]" /> Nuevo BL
            </a>
        @endcan
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <form method="GET" action="{{ route('bls.index') }}" role="search" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4">
            <div class="flex min-w-56 flex-1 flex-col gap-1.5">
                <label for="q" class="text-xs font-medium text-slate-500">Buscar</label>
                <input id="q" name="q" type="search" value="{{ request('q') }}" maxlength="50" placeholder="Número de BL o factura"
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
                <label for="estado" class="text-xs font-medium text-slate-500">Estado</label>
                <select id="estado" name="estado" class="h-10 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>{{ str_replace('_', ' ', ucfirst(strtolower($estado->value))) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="h-10 rounded-[10px] bg-slate-800 px-4 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
            @if (request()->hasAny(['q', 'cliente_id', 'estado']))
                <a href="{{ route('bls.index') }}" class="h-10 px-2 text-sm leading-10 text-marca-600 hover:underline">Limpiar</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Número BL</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Cliente</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Fecha de ingreso</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Factura</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Productos</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Bultos esperados</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($bls as $bl)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <a href="{{ route('bls.show', $bl) }}" class="font-mono text-[13px] font-semibold text-marca-700 hover:underline">{{ $bl->numero_bl }}</a>
                        </td>
                        <td class="px-4 py-3" title="{{ $bl->cliente->razon_social }}">{{ $bl->cliente->codigo_corto }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $bl->fecha_ingreso->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $bl->factura_comercial }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $bl->detalles_count }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ (int) $bl->detalles_sum_bultos_esperados }}</td>
                        <td class="px-4 py-3"><x-estado-bl :estado="$bl->estado" /></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('bls.show', $bl) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Ver</a>
                                @can('update', $bl)
                                    <a href="{{ route('bls.edit', $bl) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Editar</a>
                                @endcan
                                @can('delete', $bl)
                                    @php
                                        $accion = [
                                            'url' => route('bls.destroy', $bl), 'metodo' => 'DELETE', 'peligro' => true,
                                            'titulo' => '¿Eliminar el BL '.$bl->numero_bl.'?',
                                            'detalle' => $bl->cliente->codigo_corto.' · Factura '.$bl->factura_comercial,
                                            'puntos' => ['Se borra el BL con su manifiesto.', 'Solo es posible porque aún no tiene bultos ni pallets.', 'La eliminación queda en la bitácora.'],
                                            'boton' => 'Eliminar BL',
                                        ];
                                    @endphp
                                    <button type="button" @click="$dispatch('confirmar', {{ Js::from($accion) }})"
                                            class="h-9 rounded-lg border border-red-300 px-3 text-[13px] text-red-700 hover:bg-red-50">Eliminar</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">
                            @if (request()->hasAny(['q', 'cliente_id', 'estado']))
                                No hay BL con esos filtros.
                            @else
                                Registre el primer BL con su manifiesto de productos.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($bls->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $bls->links() }}</div>
        @endif
    </div>
@endsection

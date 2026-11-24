@extends('layouts.app')

@section('titulo', 'Aforos')

@section('migas')
    Entrada <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Aforos</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Aforos</h1>
            <p class="mt-1 text-sm text-slate-500">Inventario detallado de un BL recibido: el operario cuenta cada línea de bulto desde la app.</p>
        </div>
        <a href="{{ route('aforos.create') }}"
           class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
            <x-icono nombre="mas" class="size-[18px]" /> Activar aforo
        </a>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <form method="GET" action="{{ route('aforos.index') }}" role="search" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4">
            <div class="flex min-w-60 flex-1 flex-col gap-1.5">
                <label for="q" class="text-xs font-medium text-slate-500">Buscar</label>
                <input id="q" name="q" type="search" value="{{ request('q') }}" maxlength="30" placeholder="Número de BL"
                       class="h-10 rounded-[10px] border border-slate-300 px-3 text-sm outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
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
            @if (request()->hasAny(['q', 'estado']))
                <a href="{{ route('aforos.index') }}" class="h-10 px-2 text-sm leading-10 text-marca-600 hover:underline">Limpiar</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Aforo</th>
                        <th scope="col" class="px-4 py-3 font-semibold">BL</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Cliente</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Líneas contadas</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Activado</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($aforos as $aforo)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <a href="{{ route('aforos.show', $aforo) }}" class="font-mono text-[13px] font-semibold text-marca-700 hover:underline">AFO-{{ $aforo->id }}</a>
                        </td>
                        <td class="px-4 py-3 font-mono text-[13px]">{{ $aforo->bl->numero_bl }}</td>
                        <td class="px-4 py-3" title="{{ $aforo->bl->cliente->razon_social }}">{{ $aforo->bl->cliente->codigo_corto }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $aforo->contadas_count }} / {{ $aforo->detalles_count }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $aforo->created_at?->timezone(config('kosmos.zona_horaria'))->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3"><x-estado-aforo :estado="$aforo->estado" /></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <a href="{{ route('aforos.show', $aforo) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Ver</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                            @if (request()->hasAny(['q', 'estado']))
                                No hay aforos con esos filtros.
                            @else
                                Aún no hay aforos. Actívelo sobre un BL recibido cuando el cliente o el operador lo soliciten.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($aforos->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $aforos->links() }}</div>
        @endif
    </div>
@endsection

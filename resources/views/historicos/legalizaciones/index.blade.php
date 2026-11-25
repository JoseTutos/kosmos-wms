@extends('layouts.app')

@section('titulo', 'Histórico de legalizaciones')

@section('migas')
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Históricos</span>
@endsection

@section('contenido')
    @php
        $filtros = request()->only(['desde', 'hasta', 'cliente_id', 'bl', 'numero']);
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Histórico de legalizaciones</h1>
            <p class="mt-1 text-sm text-slate-500">Registro de solo lectura para auditoría. Nada se borra: se conserva al menos 5 años.</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('historicos.legalizaciones.exportar', ['formato' => 'xlsx'] + $filtros) }}"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
                <x-icono nombre="abajo" class="size-[18px]" /> Excel
            </a>
            <a href="{{ route('historicos.legalizaciones.exportar', ['formato' => 'csv'] + $filtros) }}"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
                <x-icono nombre="abajo" class="size-[18px]" /> CSV
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('historicos.legalizaciones.index') }}" class="flex flex-wrap items-end gap-3 rounded-[14px] border border-slate-200 bg-white p-5">
        <div class="flex flex-col gap-1.5">
            <label for="desde" class="text-sm font-medium">Desde</label>
            <input id="desde" name="desde" type="date" value="{{ request('desde') }}" class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="hasta" class="text-sm font-medium">Hasta</label>
            <input id="hasta" name="hasta" type="date" value="{{ request('hasta') }}" class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        <div class="flex min-w-56 flex-col gap-1.5">
            <label for="cliente_id" class="text-sm font-medium">Cliente</label>
            <select id="cliente_id" name="cliente_id" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                <option value="">Todos</option>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected((string) request('cliente_id') === (string) $cliente->id)>{{ $cliente->codigo_corto }} · {{ $cliente->razon_social }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex min-w-40 flex-1 flex-col gap-1.5">
            <label for="bl" class="text-sm font-medium">BL</label>
            <input id="bl" name="bl" type="search" value="{{ request('bl') }}" maxlength="30" class="h-[46px] rounded-[10px] border border-slate-300 px-3 font-mono text-[15px]">
        </div>
        <div class="flex min-w-40 flex-1 flex-col gap-1.5">
            <label for="numero" class="text-sm font-medium">N.º de autorización</label>
            <input id="numero" name="numero" type="search" value="{{ request('numero') }}" maxlength="60" class="h-[46px] rounded-[10px] border border-slate-300 px-3 font-mono text-[15px]">
        </div>
        <button type="submit" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
        @if ($filtros)
            <a href="{{ route('historicos.legalizaciones.index') }}" class="inline-flex h-[46px] items-center rounded-[10px] border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50">Limpiar</a>
        @endif
    </form>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Fecha y hora</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Legalización</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Usuario</th>
                        <th scope="col" class="px-4 py-3 font-semibold">BL · Cliente</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Bultos</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Unidades · seriales</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Documento</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Soporte</span></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($legalizaciones as $legalizacion)
                    <tr class="border-t border-slate-100 align-top hover:bg-slate-50">
                        <td class="px-4 py-3 tabular-nums">{{ $legalizacion->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('historicos.legalizaciones.show', $legalizacion) }}" class="font-mono text-[13px] font-semibold text-marca-600 hover:underline">{{ $legalizacion->consecutivo }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $legalizacion->usuario->nombre }}</td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-[13px]">{{ $legalizacion->bl->numero_bl }}</span>
                            <span class="text-xs text-slate-500">· {{ $legalizacion->bl->cliente->codigo_corto }}</span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ (int) $legalizacion->bultos }}</td>
                        <td class="px-4 py-3 tabular-nums">
                            {{ $legalizacion->unidades ?? '—' }}
                            @if ((int) $legalizacion->seriales > 0)
                                <span class="block text-xs text-slate-500">{{ (int) $legalizacion->seriales }} seriales</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="block">{{ $legalizacion->tipo_documento->etiqueta() }}</span>
                            <span class="font-mono text-xs text-slate-500">{{ $legalizacion->numero_autorizacion }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('historicos.legalizaciones.soporte', $legalizacion) }}" class="text-sm text-marca-600 hover:underline">Soporte</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No hay legalizaciones con estos filtros.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $legalizaciones->links() }}
@endsection

@extends('layouts.app')

@use('App\Models\Ajuste')

@section('titulo', 'Ajustes de inventario')

@section('migas')
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Ajustes</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Ajustes de inventario</h1>
            <p class="mt-1 text-sm text-slate-500">Correcciones de cantidades con motivo y descripción. Son irreversibles: un ajuste equivocado se corrige con otro ajuste.</p>
        </div>
        @can('create', Ajuste::class)
            <a href="{{ route('ajustes.create') }}"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
                <x-icono nombre="check" class="size-[18px]" /> Nuevo ajuste
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('ajustes.index') }}" class="flex flex-wrap items-end gap-3 rounded-[14px] border border-slate-200 bg-white p-5">
        <div class="flex min-w-56 flex-1 flex-col gap-1.5">
            <label for="q" class="text-sm font-medium">Buscar</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" maxlength="50" placeholder="AJ, bulto, BL o SKU"
                   class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        @foreach (['motivo' => [$motivos, 'Motivo'], 'tipo' => [$tipos, 'Tipo'], 'estado' => [$estados, 'Estado']] as $campo => [$opciones, $rotulo])
            <div class="flex flex-col gap-1.5">
                <label for="{{ $campo }}" class="text-sm font-medium">{{ $rotulo }}</label>
                <select id="{{ $campo }}" name="{{ $campo }}" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                    <option value="">Todos</option>
                    @foreach ($opciones as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(request($campo) === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div class="flex flex-col gap-1.5">
            <label for="desde" class="text-sm font-medium">Desde</label>
            <input id="desde" name="desde" type="date" value="{{ request('desde') }}" class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="hasta" class="text-sm font-medium">Hasta</label>
            <input id="hasta" name="hasta" type="date" value="{{ request('hasta') }}" class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        <button type="submit" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
        @if (request()->hasAny(['q', 'motivo', 'tipo', 'estado', 'desde', 'hasta']))
            <a href="{{ route('ajustes.index') }}" class="inline-flex h-[46px] items-center rounded-[10px] border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50">Limpiar</a>
        @endif
    </form>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Ajuste</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Fecha</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Bulto · Producto</th>
                        <th scope="col" class="px-4 py-3 font-semibold">BL</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Cantidad</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Motivo</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($ajustes as $ajuste)
                    @php
                        $positivo = $ajuste->tipo === \App\Enums\TipoAjuste::POSITIVO;
                    @endphp
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('ajustes.show', $ajuste) }}" class="font-mono text-[13px] font-semibold text-marca-600 hover:underline">{{ $ajuste->consecutivo }}</a>
                        </td>
                        <td class="px-4 py-3 tabular-nums">{{ $ajuste->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-[13px]">{{ $ajuste->bultoDetalle->bulto->codigo }}</span>
                            <span class="text-xs text-slate-500">· {{ $ajuste->bultoDetalle->producto->sku }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-[13px]">{{ $ajuste->bultoDetalle->bl->numero_bl }}</td>
                        <td class="px-4 py-3">{{ $ajuste->estado_afectado->etiqueta() }}</td>
                        <td @class(['px-4 py-3 text-right font-semibold tabular-nums', 'text-green-700' => $positivo, 'text-red-700' => ! $positivo])>
                            {{ $positivo ? '+' : '−' }}{{ $ajuste->cantidad }} <span class="text-xs font-normal text-slate-500">{{ $ajuste->bultoDetalle->producto->unidad_medida->value }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $ajuste->motivo->etiqueta() }}</td>
                        <td class="px-4 py-3">{{ $ajuste->usuario->nombre }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No hay ajustes registrados con estos filtros.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $ajustes->links() }}
@endsection

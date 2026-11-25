@extends('layouts.app')

@use('App\Models\Legalizacion')

@section('titulo', 'Legalizaciones')

@section('migas')
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Legalizaciones</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Legalizaciones</h1>
            <p class="mt-1 text-sm text-slate-500">Mercancía que pasó de cuarentena a legalizada. Una legalización no se edita ni se borra: un error se corrige con un ajuste.</p>
        </div>
        @can('create', Legalizacion::class)
            <a href="{{ route('legalizaciones.create') }}"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
                <x-icono nombre="check" class="size-[18px]" /> Nueva legalización
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('legalizaciones.index') }}" class="flex flex-wrap items-end gap-3 rounded-[14px] border border-slate-200 bg-white p-5">
        <div class="flex min-w-64 flex-1 flex-col gap-1.5">
            <label for="q" class="text-sm font-medium">Buscar</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" maxlength="60" placeholder="LEG, número de autorización o BL"
                   class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
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
        <button type="submit" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
        @if (request()->hasAny(['q', 'cliente_id']))
            <a href="{{ route('legalizaciones.index') }}" class="inline-flex h-[46px] items-center rounded-[10px] border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50">Limpiar</a>
        @endif
    </form>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Legalización</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Fecha</th>
                        <th scope="col" class="px-4 py-3 font-semibold">BL · Cliente</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Autorización</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Líneas</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($legalizaciones as $legalizacion)
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('legalizaciones.show', $legalizacion) }}" class="font-mono text-[13px] font-semibold text-marca-600 hover:underline">{{ $legalizacion->consecutivo }}</a>
                        </td>
                        <td class="px-4 py-3 tabular-nums">{{ $legalizacion->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-[13px]">{{ $legalizacion->bl->numero_bl }}</span>
                            <span class="text-xs text-slate-500">· {{ $legalizacion->bl->cliente->codigo_corto }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="block">{{ $legalizacion->tipo_documento->etiqueta() }}</span>
                            <span class="font-mono text-xs text-slate-500">{{ $legalizacion->numero_autorizacion }}</span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $legalizacion->detalles_count }}</td>
                        <td class="px-4 py-3">{{ $legalizacion->usuario->nombre }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No hay legalizaciones registradas con estos filtros.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $legalizaciones->links() }}
@endsection

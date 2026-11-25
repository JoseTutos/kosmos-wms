@extends('layouts.app')

@use('App\Models\Legalizacion')
@use('App\Services\Control\EstadoLegalService')
@use('App\Support\Milesimas')

@section('titulo', 'Estado legal · '.$bl->numero_bl)

@section('migas')
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('estado-legal.index') }}" class="hover:underline">Estado legal</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">{{ $bl->numero_bl }}</span>
@endsection

@section('contenido')
    @php
        $enCuarentena = $totales && Milesimas::de((string) $totales->total_cuarentena) > 0;
        $indicador = $totales ? EstadoLegalService::indicador($totales) : null;
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-mono text-2xl font-semibold tracking-tight">{{ $bl->numero_bl }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $bl->cliente->codigo_corto }} · {{ $bl->cliente->razon_social }} · ingreso {{ $bl->fecha_ingreso->format('d/m/Y') }}
                @if ($totales) · {{ (int) $totales->dias }} días @endif
                · {{ $bl->estado->value }}
            </p>
        </div>
        @if ($legalizable && $enCuarentena && Route::has('legalizaciones.create'))
            @can('create', Legalizacion::class)
                <a href="{{ route('legalizaciones.create', ['bl' => $bl->id]) }}"
                   class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
                    <x-icono nombre="check" class="size-[18px]" /> Legalizar este BL
                </a>
            @endcan
        @endif
    </div>

    <div class="grid max-w-[900px] grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">En cuarentena</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $totales->total_cuarentena ?? '0.000' }} <span class="text-base font-normal text-slate-500">/ {{ $totales->total_recibida ?? '0.000' }}</span></p>
            <p class="text-xs text-slate-500">
                @if (! $enCuarentena)
                    Todo legalizado: el BL ya no aparece en el estado legal activo.
                @elseif ($indicador === EstadoLegalService::INDICADOR_AFORO)
                    Hay un aforo en proceso.
                @elseif ($indicador === EstadoLegalService::INDICADOR_TOTAL)
                    Nada legalizado todavía.
                @else
                    Legalización parcial.
                @endif
            </p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Legalizado</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $totales->total_legalizada ?? '0.000' }}</p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Preinspección · discrepancia</p>
            <p class="mt-1 font-semibold">{{ $bl->fecha_preinspeccion?->format('d/m/Y') ?? 'Sin preinspección' }}</p>
            <p class="text-sm tabular-nums text-slate-500">{{ (int) $bl->discrepancia_bultos > 0 ? '+' : '' }}{{ (int) $bl->discrepancia_bultos }} bultos</p>
        </div>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-[15px] font-semibold">Detalle por bulto y producto</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Bulto</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Pallet · Lugar</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Producto</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Recibida</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Legalizada</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">En cuarentena</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($lineas as $linea)
                    @php
                        $pendiente = Milesimas::de($linea->en_cuarentena) > 0;
                        $pallet = $linea->bulto->pallet;
                        $lugar = $pallet?->bodega?->codigo.($pallet?->ubicacion ? ' / '.$pallet->ubicacion->codigo : '');
                    @endphp
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <span class="font-mono text-[13px] font-semibold">{{ $linea->bulto->codigo }}</span>
                            <span class="block text-xs text-slate-500">{{ $linea->bulto->estado->value }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-[13px]">{{ $pallet?->codigo ?? '—' }} · {{ $lugar ?: 'sin ubicar' }}</td>
                        <td class="px-4 py-3" title="{{ $linea->producto->descripcion }}">{{ $linea->producto->sku }} <span class="text-xs text-slate-500">{{ $linea->producto->unidad_medida->value }}</span></td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $linea->cantidad_recibida }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $linea->cantidad_legalizada }}</td>
                        <td @class(['px-4 py-3 text-right font-semibold tabular-nums', 'text-red-700' => $pendiente, 'text-slate-400' => ! $pendiente])>{{ $linea->en_cuarentena }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">El BL no tiene bultos.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

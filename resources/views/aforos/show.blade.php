@extends('layouts.app')

@use('App\Support\Milesimas')
@use('Illuminate\Support\Js')

@section('titulo', 'Aforo AFO-'.$aforo->id)

@section('migas')
    Entrada <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('aforos.index') }}" class="hover:underline">Aforos</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">AFO-{{ $aforo->id }}</span>
@endsection

@section('contenido')
    @php
        $total = $aforo->detalles->count();
        $contadas = $aforo->detalles->whereNotNull('cantidad_contada')->count();
        $conDiferencia = $aforo->detalles->filter(fn ($d) => $d->cantidad_contada !== null && Milesimas::de($d->diferencia) !== 0)->count();
        $pct = $total > 0 ? (int) round($contadas * 100 / $total) : 0;
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-2xl font-semibold tracking-tight">AFO-{{ $aforo->id }}</h1>
                <x-estado-aforo :estado="$aforo->estado" />
            </div>
            <p class="mt-1 text-sm text-slate-500">
                BL
                @if (Route::has('bls.show'))
                    <a href="{{ route('bls.show', $aforo->bl) }}" class="font-mono text-marca-600 hover:underline">{{ $aforo->bl->numero_bl }}</a>
                @else
                    <span class="font-mono">{{ $aforo->bl->numero_bl }}</span>
                @endif
                · {{ $aforo->bl->cliente->codigo_corto }} · {{ $aforo->bl->cliente->razon_social }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2.5">
            @can('reporte', $aforo)
                <a href="{{ route('aforos.reporte', [$aforo, 'pdf']) }}"
                   class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
                    <x-icono nombre="abajo" class="size-[18px]" /> Reporte PDF
                </a>
                <a href="{{ route('aforos.reporte', [$aforo, 'xlsx']) }}"
                   class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
                    <x-icono nombre="abajo" class="size-[18px]" /> Reporte Excel
                </a>
            @endcan

            @can('cancelar', $aforo)
                @php
                    $cancelar = [
                        'url' => route('aforos.cancelar', $aforo), 'metodo' => 'PATCH', 'peligro' => true,
                        'titulo' => '¿Cancelar el aforo AFO-'.$aforo->id.'?',
                        'detalle' => 'BL '.$aforo->bl->numero_bl.' · '.$contadas.' de '.$total.' líneas contadas',
                        'puntos' => ['Los conteos no se aplican a las cantidades.', 'Se borran los seriales generados en este aforo.', 'Los bultos vuelven a UBICADO. Todo queda en la bitácora.'],
                        'boton' => 'Cancelar aforo',
                    ];
                @endphp
                <button type="button" @click="$dispatch('confirmar', {{ Js::from($cancelar) }})"
                        class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-red-300 bg-white px-4 text-sm font-medium text-red-700 hover:bg-red-50">
                    <x-icono nombre="cerrar" class="size-[18px]" /> Cancelar aforo
                </button>
            @endcan

            @can('cerrar', $aforo)
                @php
                    $cerrar = [
                        'url' => route('aforos.cerrar', $aforo), 'metodo' => 'PATCH',
                        'titulo' => '¿Cerrar el aforo AFO-'.$aforo->id.'?',
                        'detalle' => $conDiferencia.' de '.$total.' líneas con diferencia',
                        'puntos' => ['Cada conteo reemplaza la cantidad recibida y corrige la cuarentena (movimiento AFORO).', 'Se genera el reporte de diferencias en PDF y Excel.', 'No se puede deshacer: después solo se corrige con un ajuste.'],
                        'boton' => 'Cerrar aforo',
                    ];
                @endphp
                <button type="button" @click="$dispatch('confirmar', {{ Js::from($cerrar) }})" @disabled($contadas < $total)
                        title="{{ $contadas < $total ? 'Faltan líneas por contar' : 'Aplicar los conteos' }}"
                        class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700 disabled:opacity-50">
                    <x-icono nombre="check" class="size-[18px]" /> Cerrar aforo
                </button>
            @endcan
        </div>
    </div>

    {{-- Avance --}}
    <div class="grid max-w-[900px] grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Líneas contadas</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $contadas }} <span class="text-base font-normal text-slate-500">/ {{ $total }}</span></p>
            <div class="mt-2 h-2 rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                <div class="h-2 rounded-full bg-marca-600" style="width: {{ $pct }}%"></div>
            </div>
        </div>
        <div @class(['rounded-[14px] border bg-white p-5', 'border-acento-300' => $conDiferencia > 0, 'border-slate-200' => $conDiferencia === 0])>
            <p class="text-[13px] text-slate-500">Con diferencia</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $conDiferencia }}</p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Seriales generados</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $aforo->detalles->sum(fn ($d) => $d->bultoDetalle->seriales_count) }}</p>
        </div>
    </div>

    @if ($aforo->estaEnProceso())
        <div class="flex items-start gap-2.5 rounded-[14px] border border-marca-100 bg-marca-50 p-4 text-sm text-slate-700">
            <x-icono nombre="info" class="mt-px size-[18px] text-marca-600" />
            <span>Los operarios cuentan desde la app (Aforos → AFO-{{ $aforo->id }}). Recargue la página para ver el avance. Para cerrar, todas las líneas deben estar contadas.</span>
        </div>
    @endif

    {{-- Líneas --}}
    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Bulto</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Pallet · Bodega</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Producto</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Manifiesto</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Contada</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Diferencia</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Seriales</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($aforo->detalles as $detalle)
                    @php
                        $linea = $detalle->bultoDetalle;
                        $dif = $detalle->cantidad_contada === null ? null : Milesimas::de($detalle->diferencia);
                    @endphp
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-mono text-[13px] font-semibold">{{ $linea->bulto->codigo }}</td>
                        <td class="px-4 py-3 font-mono text-[13px]">{{ $linea->bulto->pallet?->codigo }} · {{ $linea->bulto->pallet?->bodega?->codigo }}</td>
                        <td class="px-4 py-3" title="{{ $linea->producto->descripcion }}">{{ $linea->producto->sku }} <span class="text-xs text-slate-500">{{ $linea->producto->unidad_medida->value }}</span></td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $detalle->cantidad_manifiesto }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            @if ($detalle->cantidad_contada === null)
                                <span class="text-slate-400">Pendiente</span>
                            @else
                                {{ $detalle->cantidad_contada }}
                            @endif
                        </td>
                        <td @class(['px-4 py-3 text-right font-semibold tabular-nums', 'text-red-700' => $dif !== null && $dif < 0, 'text-green-700' => $dif !== null && $dif > 0, 'text-slate-400' => $dif === null || $dif === 0])>
                            {{ $dif === null ? '—' : ($dif > 0 ? '+' : '').$detalle->diferencia }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $linea->seriales_count ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Este aforo no tiene líneas.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

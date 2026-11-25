@extends('layouts.app')

@use('App\Enums\TipoAjuste')

@section('titulo', $ajuste->consecutivo)

@section('migas')
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('ajustes.index') }}" class="hover:underline">Ajustes</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">{{ $ajuste->consecutivo }}</span>
@endsection

@section('contenido')
    @php
        $linea = $ajuste->bultoDetalle;
        $positivo = $ajuste->tipo === TipoAjuste::POSITIVO;
        $unidad = $linea->producto->unidad_medida->value;
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-mono text-2xl font-semibold tracking-tight">{{ $ajuste->consecutivo }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                BL <span class="font-mono">{{ $linea->bl->numero_bl }}</span> · {{ $linea->bl->cliente->codigo_corto }} · {{ $linea->bl->cliente->razon_social }}
            </p>
        </div>
        @if ($ajuste->soporte_path)
            <a href="{{ route('ajustes.soporte', $ajuste) }}"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
                <x-icono nombre="abajo" class="size-[18px]" /> Descargar soporte
            </a>
        @endif
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Ajuste</p>
            <p @class(['mt-1 text-2xl font-semibold tabular-nums', 'text-green-700' => $positivo, 'text-red-700' => ! $positivo])>
                {{ $positivo ? '+' : '−' }}{{ $ajuste->cantidad }} <span class="text-base font-normal text-slate-500">{{ $unidad }}</span>
            </p>
            <p class="text-sm text-slate-500">{{ $ajuste->tipo->etiqueta() }} · {{ $ajuste->estado_afectado->etiqueta() }}</p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Bulto · Producto</p>
            <p class="mt-1 font-mono font-semibold">{{ $linea->bulto->codigo }}</p>
            <p class="text-sm" title="{{ $linea->producto->descripcion }}">{{ $linea->producto->sku }} · {{ $linea->bulto->pallet?->codigo }} · {{ $linea->bulto->pallet?->bodega?->codigo }}</p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Motivo</p>
            <p class="mt-1 font-semibold">{{ $ajuste->motivo->etiqueta() }}</p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Registró</p>
            <p class="mt-1 font-semibold">{{ $ajuste->usuario->nombre }}</p>
            <p class="text-sm tabular-nums text-slate-500">{{ $ajuste->created_at?->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-4 rounded-[14px] border border-slate-200 bg-white p-5">
        <div>
            <h2 class="text-[15px] font-semibold">Descripción</h2>
            <p class="mt-1 whitespace-pre-line text-sm">{{ $ajuste->descripcion }}</p>
        </div>

        @if ($ajuste->movimientoCorregido)
            <div>
                <h2 class="text-[15px] font-semibold">Corrige el movimiento</h2>
                <p class="mt-1 font-mono text-sm">
                    #{{ $ajuste->movimientoCorregido->id }} · {{ $ajuste->movimientoCorregido->tipo->value }}
                    · {{ $ajuste->movimientoCorregido->cantidad }} {{ $ajuste->movimientoCorregido->estado_legal?->etiqueta() }}
                    · {{ $ajuste->movimientoCorregido->documento_ref ?? 'sin referencia' }}
                    · {{ $ajuste->movimientoCorregido->created_at?->format('d/m/Y H:i') }}
                </p>
            </div>
        @endif

        @if ($movimiento)
            <div>
                <h2 class="text-[15px] font-semibold">Movimiento generado</h2>
                <p class="mt-1 font-mono text-sm">#{{ $movimiento->id }} · AJUSTE · {{ $movimiento->cantidad }} {{ $movimiento->estado_legal?->etiqueta() }}</p>
            </div>
        @endif

        <p class="text-xs text-slate-500">Registro de solo lectura: un ajuste no se edita ni se borra.</p>
    </div>
@endsection

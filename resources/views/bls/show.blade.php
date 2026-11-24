@extends('layouts.app')

@section('titulo', 'BL '.$bl->numero_bl)

@section('migas')
    Entrada <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('bls.index') }}" class="hover:underline">BL</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">{{ $bl->numero_bl }}</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-2xl font-semibold tracking-tight">{{ $bl->numero_bl }}</h1>
                <x-estado-bl :estado="$bl->estado" />
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ $bl->cliente->codigo_corto }} · {{ $bl->cliente->razon_social }}</p>
        </div>

        <div class="flex flex-wrap gap-2.5">
            @can('update', $bl)
                <a href="{{ route('bls.edit', $bl) }}"
                   class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
                    <x-icono nombre="editar" class="size-[18px]" /> Editar
                </a>
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
                        class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-red-300 bg-white px-4 text-sm font-medium text-red-700 hover:bg-red-50">
                    <x-icono nombre="papelera" class="size-[18px]" /> Eliminar
                </button>
            @endcan
        </div>
    </div>

    {{-- ===================== Datos del BL ===================== --}}
    <dl class="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-4 rounded-[14px] border border-slate-200 bg-white p-5">
        <div>
            <dt class="text-xs font-medium text-slate-500">Fecha de ingreso</dt>
            <dd class="mt-1 text-sm font-medium tabular-nums">{{ $bl->fecha_ingreso->format('d/m/Y') }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-slate-500">Factura comercial</dt>
            <dd class="mt-1 text-sm font-medium">{{ $bl->factura_comercial }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-slate-500">NIT del cliente</dt>
            <dd class="mt-1 text-sm font-medium tabular-nums">{{ $bl->cliente->nit }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-slate-500">Recepción</dt>
            <dd class="mt-1 text-sm font-medium tabular-nums">{{ $bl->bultos_count }} bultos · {{ $bl->pallets_count }} pallets</dd>
        </div>
        @if ($bl->observacion_recepcion)
            <div class="col-span-full">
                <dt class="text-xs font-medium text-slate-500">Observación de la recepción</dt>
                <dd class="mt-1 text-sm">{{ $bl->observacion_recepcion }}</dd>
            </div>
        @endif
    </dl>

    @include('bls._detalle')

    {{-- ===================== Preinspección (B6, RS-28) ===================== --}}
    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-3">
            <h2 class="text-base font-semibold">Preinspección</h2>
            <p class="text-xs text-slate-500">Registro documental del trámite externo. Editable hasta que el BL quede recibido.</p>
        </div>

        <div class="flex flex-col gap-4 p-4">
            <div class="flex flex-wrap gap-x-8 gap-y-2 text-sm">
                <span><span class="text-slate-500">Fecha:</span> {{ $bl->fecha_preinspeccion?->format('d/m/Y') ?? 'Sin registrar' }}</span>
                <span>
                    <span class="text-slate-500">Acta:</span>
                    @if ($bl->acta_preinspeccion_path)
                        <a href="{{ route('bls.acta', $bl) }}" class="text-marca-600 hover:underline">Descargar</a>
                    @else
                        Sin acta
                    @endif
                </span>
            </div>

            @can('preinspeccion', $bl)
                <form method="POST" action="{{ route('bls.preinspeccion', $bl) }}" enctype="multipart/form-data"
                      class="flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                    @csrf
                    @method('PATCH')
                    <div class="flex flex-col gap-1.5">
                        <label for="fecha_preinspeccion" class="text-xs font-medium text-slate-500">Fecha</label>
                        <input id="fecha_preinspeccion" name="fecha_preinspeccion" type="date" max="{{ now()->format('Y-m-d') }}"
                               value="{{ old('fecha_preinspeccion', $bl->fecha_preinspeccion?->format('Y-m-d')) }}"
                               class="h-10 rounded-[10px] border border-slate-300 px-3 text-sm">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="acta" class="text-xs font-medium text-slate-500">Acta (PDF o imagen, 10 MB)</label>
                        <input id="acta" name="acta" type="file" accept=".pdf,.jpg,.jpeg,.png"
                               class="rounded-[10px] border border-slate-300 bg-white p-1.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-sm">
                    </div>
                    @if ($bl->acta_preinspeccion_path)
                        <label class="inline-flex h-10 items-center gap-1.5 text-sm text-slate-600">
                            <input type="checkbox" name="quitar_acta" value="1" class="rounded border-slate-300"> Quitar acta
                        </label>
                    @endif
                    <button type="submit" class="h-10 rounded-[10px] bg-slate-800 px-4 text-sm font-medium text-white hover:bg-slate-700">Guardar preinspección</button>
                </form>
                @error('fecha_preinspeccion') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                @error('acta') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            @endcan
        </div>
    </div>
@endsection

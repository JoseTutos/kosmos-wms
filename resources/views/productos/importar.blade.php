@extends('layouts.app')

@section('titulo', 'Importar productos')

@section('migas')
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('productos.index') }}" class="hover:underline">Productos</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Importar CSV</span>
@endsection

@section('contenido')
    @php
        $errores = session('errores_importacion', []);
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Importar productos</h1>
            <p class="mt-1 text-sm text-slate-500">Cargue varios productos de un cliente desde un archivo CSV. El SKU se genera solo para cada uno.</p>
        </div>
        <a href="{{ route('productos.plantilla') }}"
           class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
            <x-icono nombre="abajo" class="size-[18px]" /> Descargar plantilla
        </a>
    </div>

    {{-- Errores por fila: la importación es todo o nada --}}
    @if ($errores)
        <div role="alert" class="rounded-[14px] border border-red-200 bg-white">
            <div class="flex items-start gap-2.5 border-b border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                <x-icono nombre="error" class="mt-0.5 size-[18px]" />
                <span>
                    <strong>No se importó ningún producto.</strong>
                    El archivo tiene {{ count($errores) }} {{ count($errores) === 1 ? 'error' : 'errores' }}{{ count($errores) >= 100 ? ' (se muestran los primeros 100)' : '' }}.
                    Corríjalos y vuelva a subir el archivo completo.
                </span>
            </div>
            <div class="max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th scope="col" class="w-24 px-5 py-3 font-semibold">Fila</th>
                            <th scope="col" class="px-5 py-3 font-semibold">Problema</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($errores as $error)
                            <tr class="border-t border-slate-100">
                                <td class="px-5 py-2.5 font-mono text-[13px] tabular-nums">{{ $error['fila'] > 0 ? $error['fila'] : '—' }}</td>
                                <td class="px-5 py-2.5">{{ $error['mensaje'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
        {{-- Formulario --}}
        <form method="POST" action="{{ route('productos.importar.store') }}" enctype="multipart/form-data"
              x-data="{ enviando: false }" @submit="enviando = true"
              class="flex flex-col gap-4 rounded-[14px] border border-slate-200 bg-white p-5">
            @csrf

            <div class="flex flex-col gap-1.5">
                <label for="cliente_id" class="text-sm font-medium">Cliente</label>
                <select id="cliente_id" name="cliente_id" required class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                    <option value="">Seleccione el cliente dueño de los productos…</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected((string) old('cliente_id') === (string) $cliente->id)>{{ $cliente->codigo_corto }} · {{ $cliente->razon_social }}</option>
                    @endforeach
                </select>
                @if ($clientes->isEmpty())
                    <span class="text-xs text-slate-500">No hay clientes activos. Cree o reactive uno antes de importar.</span>
                @endif
                @error('cliente_id') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="archivo" class="text-sm font-medium">Archivo CSV</label>
                <input id="archivo" name="archivo" type="file" required accept=".csv,.txt,text/csv"
                       class="rounded-[10px] border border-slate-300 p-2 text-sm">
                <span class="text-xs text-slate-500">Máximo {{ number_format($maximo, 0, ',', '.') }} productos por archivo.</span>
                @error('archivo') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2.5 border-t border-slate-200 pt-4">
                <a href="{{ route('productos.index') }}" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-5 text-sm font-medium hover:bg-slate-50">Cancelar</a>
                <button type="submit" :disabled="enviando"
                        class="inline-flex h-11 items-center gap-2 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700 disabled:opacity-50">
                    <span x-text="enviando ? 'Importando…' : 'Importar productos'">Importar productos</span>
                </button>
            </div>
        </form>

        {{-- Instrucciones --}}
        <div class="flex flex-col gap-3 rounded-[14px] border border-slate-200 bg-white p-5 text-sm">
            <h2 class="text-[15px] font-semibold">Cómo armar el archivo</h2>
            <ul class="flex list-disc flex-col gap-1.5 pl-5 text-slate-700">
                <li>Primera fila: el encabezado de la plantilla. Separador <strong>;</strong> o <strong>,</strong>.</li>
                <li><span class="font-mono text-[13px]">descripcion</span>, <span class="font-mono text-[13px]">tipo_almacenamiento</span>, <span class="font-mono text-[13px]">unidad_medida</span> y <span class="font-mono text-[13px]">peso_unitario_kg</span> son obligatorias; <span class="font-mono text-[13px]">stock_minimo</span> es opcional (0 si va vacía).</li>
                <li>Unidad de medida: UND, KG o LT. Decimales con coma o punto, hasta 3.</li>
                <li>La descripción no puede repetirse en el archivo ni con un producto que el cliente ya tenga.</li>
            </ul>
            <div class="flex items-start gap-2.5 rounded-[10px] border border-yellow-200 bg-yellow-50 p-3 text-yellow-900">
                <x-icono nombre="alerta" class="mt-0.5 size-[18px]" />
                <span>Es <strong>todo o nada</strong>: si una sola fila tiene un error, no se importa ninguna y se listan los problemas con su número de fila.</span>
            </div>
        </div>
    </div>
@endsection

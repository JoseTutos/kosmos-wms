@extends('layouts.app')

@section('titulo', 'Etiquetas')

@section('migas')
    Entrada <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Etiquetas</span>
@endsection

@section('contenido')
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Reimprimir etiqueta</h1>
        <p class="mt-1 text-sm text-slate-500">Genera la etiqueta en PDF (4 × 2 in). Toda reimpresión exige motivo y queda en la bitácora.</p>
    </div>

    @php
        $claseCampo = 'h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20';
        $ayudas = [
            'bulto'     => 'Código del bulto: BUL-DDMMAA-0000',
            'pallet'    => 'Código del pallet: PLT-DDMMAA-0000',
            'bodega'    => 'Código de la bodega, tal como está en Maestros',
            'ubicacion' => 'Bodega y ubicación separadas por /: BOD-01/A-01-01',
        ];
    @endphp

    <form method="POST" action="{{ route('etiquetas.reimprimir') }}" target="_blank"
                    x-data="{ tipo: {{ Js::from(old('tipo', in_array(request('tipo'), $tipos, true) ? request('tipo') : $tipos[0])) }}, motivo: {{ Js::from(old('motivo', '')) }}, ayudas: {{ Js::from($ayudas) }} }"
          class="flex max-w-2xl flex-col gap-5 rounded-[14px] border border-slate-200 bg-white p-6">
        @csrf

        <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="tipo" class="text-sm font-medium">Tipo de etiqueta</label>
                <select id="tipo" name="tipo" x-model="tipo" required class="{{ $claseCampo }}">
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo }}">{{ ['bulto' => 'Bulto', 'pallet' => 'Pallet', 'bodega' => 'Bodega', 'ubicacion' => 'Ubicación'][$tipo] }}</option>
                    @endforeach
                </select>
                @error('tipo') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="codigo" class="text-sm font-medium">Código</label>
                    <input id="codigo" name="codigo" type="text" required maxlength="80" autocomplete="off" value="{{ old('codigo', request('codigo')) }}"
                       class="{{ $claseCampo }} font-mono" @error('codigo') aria-invalid="true" @enderror>
                <span class="text-xs text-slate-500" x-text="ayudas[tipo]"></span>
                @error('codigo') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="motivo" class="text-sm font-medium">Motivo</label>
                <select id="motivo" name="motivo" x-model="motivo" required class="{{ $claseCampo }}">
                    <option value="" disabled>Seleccione el motivo</option>
                    @foreach ($motivos as $motivo)
                        <option value="{{ $motivo->value }}">{{ $motivo->etiqueta() }}</option>
                    @endforeach
                </select>
                @error('motivo') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col gap-1.5" x-show="motivo === 'OTRO'" x-cloak>
                <label for="detalle" class="text-sm font-medium">Detalle del motivo</label>
                <input id="detalle" name="detalle" type="text" maxlength="200" autocomplete="off" value="{{ old('detalle') }}"
                       :required="motivo === 'OTRO'" class="{{ $claseCampo }}">
                @error('detalle') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-200 pt-5">
            <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700">
                <x-icono nombre="etiqueta" class="size-[18px]" /> Generar PDF
            </button>
        </div>
    </form>
@endsection

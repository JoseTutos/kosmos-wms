@extends('layouts.app')

@use('App\Support\Milesimas')
@use('Illuminate\Support\Js')

@section('titulo', 'Activar aforo')

@section('migas')
    Entrada <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('aforos.index') }}" class="hover:underline">Aforos</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Activar</span>
@endsection

@section('contenido')
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Activar aforo</h1>
        <p class="mt-1 text-sm text-slate-500">Elija un BL recibido y los bultos ubicados que se van a contar. Mientras dure el aforo, esos bultos no se mueven ni se legalizan.</p>
    </div>

    {{-- Paso 1: el BL --}}
    <form method="GET" action="{{ route('aforos.create') }}" class="flex flex-wrap items-end gap-3 rounded-[14px] border border-slate-200 bg-white p-5">
        <div class="flex min-w-72 flex-1 flex-col gap-1.5">
            <label for="bl" class="text-sm font-medium">BL</label>
            <select id="bl" name="bl" required class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                <option value="">Seleccione un BL recibido…</option>
                @foreach ($bls as $opcion)
                    <option value="{{ $opcion->id }}" @selected($bl?->id === $opcion->id)>{{ $opcion->numero_bl }} · {{ $opcion->cliente->codigo_corto }} · {{ $opcion->fecha_ingreso->format('d/m/Y') }}</option>
                @endforeach
            </select>
            @if ($bls->isEmpty())
                <span class="text-xs text-slate-500">No hay BL recibidos sin un aforo en proceso.</span>
            @endif
        </div>
        <button type="submit" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Ver bultos</button>
    </form>

    {{-- Paso 2: los bultos --}}
    @if ($bl)
        @php
            $filas = $bultos->map(fn ($b) => [
                'id'        => $b->id,
                'reservada' => $b->detalles->contains(fn ($d) => Milesimas::de($d->cantidad_reservada) > 0),
            ]);
            $elegibles = $filas->reject(fn ($f) => $f['reservada'])->pluck('id')->values();
            $marcados = collect(old('bultos', $elegibles->all()))->map(fn ($id) => (int) $id)->values();
        @endphp

        <form method="POST" action="{{ route('aforos.store') }}" class="rounded-[14px] border border-slate-200 bg-white"
              x-data="{ marcados: {{ Js::from($marcados) }}, elegibles: {{ Js::from($elegibles) }} }">
            @csrf
            <input type="hidden" name="bl_id" value="{{ $bl->id }}">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-[15px] font-semibold">Bultos ubicados del BL {{ $bl->numero_bl }}</h2>
                    <p class="text-xs text-slate-500"><span x-text="marcados.length"></span> de {{ $elegibles->count() }} elegidos</p>
                </div>
                @if ($elegibles->isNotEmpty())
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" class="rounded border-slate-300"
                               :checked="marcados.length === elegibles.length"
                               @change="marcados = $event.target.checked ? [...elegibles] : []">
                        Todos
                    </label>
                @endif
            </div>

            @if ($bultos->isEmpty())
                <p class="p-5 text-sm text-slate-500">Este BL no tiene bultos UBICADOS. Ubíquelos desde la app antes de activar el aforo.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <th scope="col" class="w-12 px-5 py-3"><span class="sr-only">Elegir</span></th>
                                <th scope="col" class="px-3 py-3 font-semibold">Bulto</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Pallet</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Ubicación</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Productos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bultos as $bulto)
                                @php
                                    $reservada = $filas->firstWhere('id', $bulto->id)['reservada'];
                                    $lugar = $bulto->pallet?->bodega?->codigo.($bulto->pallet?->ubicacion ? ' / '.$bulto->pallet->ubicacion->codigo : '');
                                @endphp
                                <tr @class(['border-t border-slate-100', 'bg-slate-50 text-slate-500' => $reservada])>
                                    <td class="px-5 py-3">
                                        <input type="checkbox" name="bultos[]" value="{{ $bulto->id }}" x-model.number="marcados"
                                               class="rounded border-slate-300" @disabled($reservada) aria-label="Elegir {{ $bulto->codigo }}">
                                    </td>
                                    <td class="px-3 py-3 font-mono text-[13px] font-semibold">{{ $bulto->codigo }}</td>
                                    <td class="px-3 py-3 font-mono text-[13px]">{{ $bulto->pallet?->codigo }}</td>
                                    <td class="px-3 py-3 font-mono text-[13px]">{{ $lugar }}</td>
                                    <td class="px-3 py-3">
                                        @foreach ($bulto->detalles as $linea)
                                            <div class="tabular-nums">{{ $linea->producto->sku }} · {{ $linea->cantidad_recibida }} {{ $linea->producto->unidad_medida->value }}</div>
                                        @endforeach
                                        @if ($reservada)
                                            <span class="text-xs text-acento-800">Tiene unidades reservadas: no puede entrar al aforo.</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @error('bultos') <p class="px-5 pt-3 text-sm text-red-700">{{ $message }}</p> @enderror

            <div class="flex justify-end gap-2.5 border-t border-slate-200 px-5 py-4">
                <a href="{{ route('aforos.index') }}" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-5 text-sm font-medium hover:bg-slate-50">Cancelar</a>
                <button type="submit" :disabled="marcados.length === 0"
                        class="inline-flex h-11 items-center gap-2 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700 disabled:opacity-50">
                    <x-icono nombre="aforo" class="size-[18px]" /> Activar aforo
                </button>
            </div>
        </form>
    @endif
@endsection

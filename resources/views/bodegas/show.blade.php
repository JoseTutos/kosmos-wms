{{--
    Kosmos WMS · Vista · CUS-003 Ficha de bodega y sus ubicaciones — BodegaController@show
    Actor: ADMIN_MAESTROS.

    Cumple:
      - CUS-003: datos de la bodega y ocupación (RS-15), con sobrecupo marcado en rojo.
      - RS-17: si la bodega maneja ubicaciones, aquí se crean, inactivan y reactivan.
        Código único dentro de la bodega; sin edición (va en la etiqueta) ni borrado.
      - No se inactiva una ubicación con pallets ubicados (lo valida el controlador).

    Pendiente: botón de etiqueta (CUS-007) cuando exista EtiquetaService.
    Rendimiento: las ubicaciones llegan con su conteo de pallets en una sola consulta.
--}}
@extends('layouts.app')

@use('App\Support\Etiquetas')
@use('Illuminate\Support\Js')

@section('titulo', $bodega->codigo.' · Bodega')
@section('migas')
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('bodegas.index') }}" class="text-marca-600 hover:text-marca-900">Bodegas</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">{{ $bodega->codigo }}</span>
@endsection

@section('contenido')
    @php $pct = (float) $bodega->porcentaje_ocupacion; @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-md bg-marca-100 px-2.5 py-1 font-mono text-sm font-semibold text-marca-900">{{ $bodega->codigo }}</span>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $bodega->nombre }}</h1>
            <x-estado :activo="$bodega->aceptaMercancia()" :texto="Etiquetas::activo($bodega->estado)" />
        </div>
        <a href="{{ route('bodegas.edit', $bodega) }}" class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
            <x-icono nombre="editar" class="size-[18px]" /> Editar bodega
        </a>
    </div>

    {{-- Indicadores --}}
    <div class="grid max-w-[900px] grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Tamaño</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $bodega->tamano_m2 }} <span class="text-base font-normal text-slate-500">m²</span></p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Ocupados</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $bodega->m2_ocupados }} <span class="text-base font-normal text-slate-500">m²</span></p>
        </div>
        <div @class(['rounded-[14px] border bg-white p-5', 'border-red-200' => $pct > 100, 'border-slate-200' => $pct <= 100])>
            <p class="text-[13px] text-slate-500">Ocupación</p>
            <div class="mt-2">@include('bodegas._ocupacion', ['bodega' => $bodega])</div>
        </div>
    </div>

    {{-- Ubicaciones internas (RS-17) --}}
    <section class="max-w-[900px] rounded-[14px] border border-slate-200 bg-white" aria-labelledby="titulo-ubicaciones">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <h2 id="titulo-ubicaciones" class="text-[15px] font-semibold">Ubicaciones internas</h2>
            @if ($bodega->maneja_ubicaciones)
                <span class="text-[13px] text-slate-500">{{ $bodega->ubicaciones->count() }} registradas</span>
            @endif
        </div>

        @if (! $bodega->maneja_ubicaciones)
            <div class="flex items-start gap-2.5 p-5 text-sm text-slate-600">
                <x-icono nombre="info" class="mt-px size-[18px] text-marca-600" />
                <span>
                    Esta bodega no maneja ubicaciones internas: al ubicar un pallet se escribe una referencia libre.
                    Si quiere codificar posiciones (A-01, A-02…), <a href="{{ route('bodegas.edit', $bodega) }}" class="font-medium text-marca-600 hover:text-marca-900">active las ubicaciones</a>.
                </span>
            </div>
        @else
            <form method="POST" action="{{ route('bodegas.ubicaciones.store', $bodega) }}" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-5">
                @csrf
                <div class="flex min-w-52 flex-col gap-1.5">
                    <label for="codigo" class="text-xs font-medium text-slate-500">Nueva ubicación</label>
                    <input id="codigo" name="codigo" type="text" value="{{ old('codigo') }}" required maxlength="20" placeholder="A-01" autocomplete="off"
                           @error('codigo') aria-invalid="true" @enderror
                           class="h-10 rounded-[10px] border border-slate-300 px-3 font-mono text-sm uppercase outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
                </div>
                <button type="submit" class="inline-flex h-10 items-center gap-1.5 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
                    <x-icono nombre="mas" class="size-4" /> Agregar
                </button>
                <p class="basis-full text-xs text-slate-500">El código no se puede cambiar después: va en la etiqueta. Si se equivoca, inactívela y cree otra.</p>
                @error('codigo') <p class="basis-full text-sm text-red-700">{{ $message }}</p> @enderror
            </form>

            @if ($bodega->ubicaciones->isEmpty())
                <p class="p-5 text-sm text-slate-500">Aún no hay ubicaciones. Agregue la primera con el campo de arriba.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <th scope="col" class="px-5 py-3 font-semibold">Código</th>
                                <th scope="col" class="px-5 py-3 text-center font-semibold">Pallets ubicados</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Estado</th>
                                <th scope="col" class="px-5 py-3 text-right font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bodega->ubicaciones as $u)
                                @php $activa = $u->estaActiva(); @endphp
                                <tr class="border-t border-slate-100">
                                    <td @class(['px-5 py-3 font-mono font-semibold', 'text-slate-500' => ! $activa])>{{ $u->codigo }}</td>
                                    <td class="px-5 py-3 text-center tabular-nums">{{ $u->pallets_ubicados }}</td>
                                    <td class="px-5 py-3"><x-estado :activo="$activa" :texto="Etiquetas::activo($u->estado)" /></td>
                                    <td class="px-5 py-3">
                                        <div class="flex justify-end">
                                            @if ($activa)
                                                @php
                                                    $accion = [
                                                        'url' => route('ubicaciones.inactivar', $u), 'metodo' => 'PATCH', 'peligro' => true,
                                                        'titulo' => '¿Inactivar la ubicación '.$u->codigo.'?',
                                                        'detalle' => 'Bodega '.$bodega->codigo.' · '.$bodega->nombre,
                                                        'puntos' => ['No se podrá ubicar mercancía en ella.', 'Solo se puede si no tiene pallets ubicados.', 'Podrá reactivarla cuando quiera.'],
                                                        'boton' => 'Inactivar ubicación',
                                                    ];
                                                @endphp
                                                <button type="button" @click="$dispatch('confirmar', {{ Js::from($accion) }})"
                                                        class="h-9 rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Inactivar</button>
                                            @else
                                                <form method="POST" action="{{ route('ubicaciones.activar', $u) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="h-9 rounded-lg border border-green-700 px-3 text-[13px] font-medium text-green-800 hover:bg-green-50">Reactivar</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </section>
@endsection

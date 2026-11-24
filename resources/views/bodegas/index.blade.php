{{--
    Kosmos WMS · Vista · CUS-003 Bodegas — BodegaController@index
    Actor: ADMIN_MAESTROS.

    Cumple:
      - CUS-003: consulta con ocupación en m² (RS-15), alta, edición, inactivación y reactivación.
      - Sobrecupo (> 100 %) permitido con alerta visual roja.
      - Inactivar solo con la bodega vacía (el controlador lo valida); abre la ventana modal.

    Rendimiento: paginado; las ubicaciones activas se cuentan en la misma consulta.
--}}
@extends('layouts.app')

@use('App\Support\Etiquetas')
@use('Illuminate\Support\Js')

@section('titulo', 'Bodegas')
@section('migas')
    Maestros <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Bodegas</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Bodegas</h1>
            <p class="mt-1 text-sm text-slate-500">La ocupación se calcula sola al ubicar y despachar mercancía.</p>
        </div>
        <a href="{{ route('bodegas.create') }}"
           class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
            <x-icono nombre="mas" class="size-[18px]" /> Nueva bodega
        </a>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <form method="GET" action="{{ route('bodegas.index') }}" role="search" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4">
            <div class="flex min-w-60 flex-1 flex-col gap-1.5">
                <label for="q" class="text-xs font-medium text-slate-500">Buscar</label>
                <input id="q" name="q" type="search" value="{{ request('q') }}" maxlength="100" placeholder="Código o nombre"
                       class="h-10 rounded-[10px] border border-slate-300 px-3 text-sm outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="estado" class="text-xs font-medium text-slate-500">Estado</label>
                <select id="estado" name="estado" class="h-10 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todas</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>{{ Etiquetas::activo($estado) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="h-10 rounded-[10px] bg-slate-800 px-4 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
            @if (request()->hasAny(['q', 'estado']))
                <a href="{{ route('bodegas.index') }}" class="h-10 px-2 text-sm leading-10 text-marca-600 hover:underline">Limpiar</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Bodega</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Ocupación</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Ubicaciones</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($bodegas as $b)
                    @php
                        $activa = $b->aceptaMercancia();
                    @endphp
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <a href="{{ route('bodegas.show', $b) }}" class="group flex items-center gap-2.5">
                                <span class="rounded-md bg-marca-100 px-2 py-1 font-mono text-xs font-semibold text-marca-900">{{ $b->codigo }}</span>
                                <span class="font-medium group-hover:text-marca-600">{{ $b->nombre }}</span>
                            </a>
                        </td>
                        <td class="px-4 py-3">@include('bodegas._ocupacion', ['bodega' => $b])</td> 
                        <td class="px-4 py-3">
                            @if ($b->maneja_ubicaciones)
                                {{ $b->ubicaciones_count }} {{ $b->ubicaciones_count === 1 ? 'activa' : 'activas' }}
                            @else
                                <span class="text-slate-500">No maneja</span>
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-estado :activo="$activa" :texto="Etiquetas::activo($b->estado)" /></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('bodegas.show', $b) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Ver</a>
                                <a href="{{ route('bodegas.edit', $b) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Editar</a>
                                @if ($activa)
                                    @php
                                        $accion = [
                                            'url' => route('bodegas.inactivar', $b), 'metodo' => 'PATCH', 'peligro' => true,
                                            'titulo' => '¿Inactivar la bodega '.$b->codigo.'?',
                                            'detalle' => $b->nombre,
                                            'puntos' => ['No aceptará mercancía nueva.', 'Solo se puede si está vacía (0 m² ocupados).', 'Podrá reactivarla cuando quiera.'],
                                            'boton' => 'Inactivar bodega',
                                        ];
                                    @endphp
                                    <button type="button" @click="$dispatch('confirmar', {{ Js::from($accion) }})"
                                            class="h-9 rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Inactivar</button>
                                @else
                                    <form method="POST" action="{{ route('bodegas.activar', $b) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="h-9 rounded-lg border border-green-700 px-3 text-[13px] font-medium text-green-800 hover:bg-green-50">Reactivar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                            @if (request()->hasAny(['q', 'estado']))
                                No hay bodegas con esos filtros.
                            @else
                                Registre su primera bodega para poder ubicar mercancía.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($bodegas->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $bodegas->links() }}</div>
        @endif
    </div>
@endsection

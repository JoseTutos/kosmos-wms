{{--
    Kosmos WMS · Vista · CUS-001 Clientes importadores — ClienteController@index
    Actor: ADMIN_MAESTROS.

    Cumple:
      - CUS-001: consulta con filtros, alta, edición, inactivación y reactivación. Sin borrar (RS-04).
      - Confirmación según el riesgo: Reactivar es directo; Inactivar abre la ventana modal
        y explica qué se bloquea y qué no (decisión de diseño).

    Seguridad: cambios por POST con @csrf/@method; datos a JS con Js::from (escapados).
    Rendimiento: paginado de 20; "productos" y "tiene inventario" vienen en la misma consulta.
--}}
@extends('layouts.app')

@use('App\Support\Etiquetas')
@use('Illuminate\Support\Js')

@section('titulo', 'Clientes')
@section('migas')
    Maestros <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Clientes</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Clientes importadores</h1>
            <p class="mt-1 text-sm text-slate-500">NIT y código corto no se pueden cambiar. Los clientes se inactivan, no se eliminan.</p>
        </div>
        <a href="{{ route('clientes.create') }}"
           class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
            <x-icono nombre="mas" class="size-[18px]" /> Nuevo cliente
        </a>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <form method="GET" action="{{ route('clientes.index') }}" role="search" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4">
            <div class="flex min-w-60 flex-1 flex-col gap-1.5">
                <label for="q" class="text-xs font-medium text-slate-500">Buscar</label>
                <input id="q" name="q" type="search" value="{{ request('q') }}" maxlength="100" placeholder="Razón social, NIT o código"
                       class="h-10 rounded-[10px] border border-slate-300 px-3 text-sm outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="estado" class="text-xs font-medium text-slate-500">Estado</label>
                <select id="estado" name="estado" class="h-10 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>{{ Etiquetas::activo($estado) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="h-10 rounded-[10px] bg-slate-800 px-4 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
            @if (request()->hasAny(['q', 'estado']))
                <a href="{{ route('clientes.index') }}" class="h-10 px-2 text-sm leading-10 text-marca-600 hover:underline">Limpiar</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Cliente</th>
                        <th scope="col" class="px-4 py-3 font-semibold">NIT</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Contacto</th>
                        <th scope="col" class="px-4 py-3 text-center font-semibold">Productos</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($clientes as $c)
                   @php
                        $activo = $c->estaActivo();
                    @endphp
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <span @class(['rounded-md px-2 py-1 font-mono text-xs font-semibold',
                                              'bg-marca-100 text-marca-900' => $activo, 'bg-slate-100 text-slate-500' => ! $activo])>{{ $c->codigo_corto }}</span>
                                <span @class(['font-medium', 'text-slate-500' => ! $activo])>{{ $c->razon_social }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-[13px]">{{ $c->nit }}</td>
                        <td class="px-4 py-3">
                            <span class="block">{{ $c->contacto_nombre }}</span>
                            <span class="block text-[13px] text-slate-500">{{ $c->telefono }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">{{ $c->productos_count }}</td>
                        <td class="px-4 py-3">
                            <x-estado :activo="$activo" :texto="Etiquetas::activo($c->estado)" />
                            @if (! $activo && $c->tiene_inventario)
                                <span class="mt-1 block text-[11px] text-yellow-800">Con mercancía en bodega</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('clientes.edit', $c) }}" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Editar</a>

                                @if ($activo)
                                    @php
                                        $puntos = ['No podrá registrar BL ni productos nuevos.',
                                                   'Su historial (BL, legalizaciones, despachos) se conserva.',
                                                   'Podrá reactivarlo cuando quiera.'];
                                        if ($c->tiene_inventario) {
                                            $puntos[] = 'Tiene mercancía en bodega: seguirá legalizándose y despachándose con normalidad.';
                                        }
                                        $accion = [
                                            'url' => route('clientes.inactivar', $c), 'metodo' => 'PATCH', 'peligro' => true,
                                            'titulo' => '¿Inactivar a '.$c->razon_social.'?',
                                            'detalle' => $c->codigo_corto.' · NIT '.$c->nit,
                                            'puntos' => $puntos, 'boton' => 'Inactivar cliente',
                                        ];
                                    @endphp
                                    <button type="button" @click="$dispatch('confirmar', {{ Js::from($accion) }})"
                                            class="h-9 rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Inactivar</button>
                                @else
                                    <form method="POST" action="{{ route('clientes.activar', $c) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="h-9 rounded-lg border border-green-700 px-3 text-[13px] font-medium text-green-800 hover:bg-green-50">Reactivar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                            @if (request()->hasAny(['q', 'estado']))
                                No hay clientes con esos filtros.
                            @else
                                Registre su primer cliente importador para empezar.
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($clientes->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $clientes->links() }}</div>
        @endif
    </div>
@endsection

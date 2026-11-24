{{--
    Kosmos WMS · Vista · Inicio según el rol (croquis 3) — ruta 'dashboard'

    Cumple:
      - RS-19: cada usuario ve solo las tareas de su rol, como tarjetas. Salen de la MISMA
        lista que el menú lateral (App\Support\MenuPrincipal), así que nunca se contradicen.
      - Decisión de diseño: bienvenida + botón con la tarea más frecuente del rol.

    Rendimiento: sin consultas a la BD; el menú es una lista en memoria filtrada por rol.
    Contadores como "[N] BL en cuarentena" se agregarán cuando exista CUS-011, desde su Query.
--}}
@extends('layouts.app')

@use('App\Support\Etiquetas')
@use('App\Support\MenuPrincipal')

@section('titulo', 'Inicio')
@section('migas', 'Inicio')

@section('contenido')
    @php
        $yo        = auth()->user();
        $accion    = MenuPrincipal::accionPrincipal($yo);
        $secciones = MenuPrincipal::secciones($yo);
    @endphp

    <section class="flex flex-wrap items-center justify-between gap-4 rounded-[14px] border border-slate-200 bg-white px-7 py-6">
        <div>
            <h1 class="text-[26px] font-semibold tracking-tight">Bienvenido, {{ \Illuminate\Support\Str::before($yo->nombre, ' ') }}</h1>
            <p class="mt-1.5 text-[15px] text-slate-600">Panel del {{ Etiquetas::rol($yo->rol) }} · Operador Logístico Zona Franca</p>
        </div>
        @if ($accion)
            <a href="{{ route($accion['ruta']) }}"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
                <x-icono nombre="mas" class="size-[18px]" /> {{ $accion['label'] }}
            </a>
        @endif
    </section>

    @forelse ($secciones as $seccion)
        <section class="flex flex-col gap-3" aria-labelledby="sec-{{ $loop->index }}">
            <h2 id="sec-{{ $loop->index }}" class="text-[15px] font-semibold text-slate-700">{{ $seccion['titulo'] }}</h2>
            <div class="grid grid-cols-[repeat(auto-fill,minmax(230px,1fr))] gap-3.5">
                @foreach ($seccion['items'] as $item)
                    <a href="{{ route($item['ruta']) }}"
                       class="flex items-start gap-3.5 rounded-[14px] border border-slate-200 bg-white p-[18px] transition hover:border-marca-600/40 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-marca-600">
                        <span @class(['flex size-10 shrink-0 items-center justify-center rounded-[10px]',
                                      'bg-yellow-50 text-yellow-900' => $item['icono'] === 'cuarentena',
                                      'bg-orange-50 text-acento-700' => $item['icono'] === 'salida',
                                      'bg-marca-100/60 text-marca-600' => ! in_array($item['icono'], ['cuarentena', 'salida'])])>
                            <x-icono :nombre="$item['icono']" />
                        </span>
                        <span>
                            <span class="block text-[15px] font-semibold text-slate-800">{{ $item['label'] }}</span>
                            <span class="mt-0.5 block text-[13px] leading-snug text-slate-500">{{ $item['desc'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <p class="rounded-[14px] border border-slate-200 bg-white p-6 text-sm text-slate-500">
            Aún no hay módulos habilitados para su rol.
        </p>
    @endforelse
@endsection

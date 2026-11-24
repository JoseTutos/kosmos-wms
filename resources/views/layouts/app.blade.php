{{--
    Kosmos WMS · Layout · Aplicación web autenticada (croquis 3 a 8)

    Cumple:
      - An. §3.1: Vista = Blade + Alpine + Tailwind. La vista no consulta modelos:
        el menú sale de App\Support\MenuPrincipal.
      - RS-19 / RE-7: el menú lateral muestra solo los módulos del rol. Es una ayuda
        visual; lo que protege es el middleware 'role' de cada ruta.
      - CUS-004: la contraseña temporal se muestra UNA sola vez, en ventana modal
        (dato flash de sesión: desaparece en la siguiente petición).
      - USA02: los mensajes de reglas incumplidas se muestran arriba del contenido.

    Secciones que define cada vista:
      @section('titulo')    título de la pestaña
      @section('migas')     ruta de navegación de la barra superior
      @section('contenido') contenido de la página
--}}
@use('App\Support\Etiquetas')
@use('App\Support\MenuPrincipal')
@php
    $yo        = auth()->user();
    $secciones = MenuPrincipal::secciones($yo);
    $clave     = session('password_temporal'); // ['clave', 'usuario', 'titulo'] o null
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titulo', 'Inicio') · Kosmos WMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-fondo font-sans text-slate-800 antialiased">
<div x-data="{ menu: false }" class="flex min-h-screen flex-col lg:flex-row">

    {{-- ===================== Menú lateral ===================== --}}
    <nav aria-label="Menú principal"
         class="flex-col gap-5 bg-marca-900 px-3 py-4 text-slate-200 lg:flex lg:w-60 lg:shrink-0"
         :class="menu ? 'flex' : 'hidden'">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-2 pb-2 pt-1">
            <span class="flex size-[34px] items-center justify-center rounded-[9px] bg-acento-600 text-[17px] font-bold text-white">K</span>
            <span class="text-base font-semibold tracking-tight text-white">Kosmos WMS</span>
        </a>

        @php $enInicio = request()->routeIs('dashboard'); @endphp
        <a href="{{ route('dashboard') }}" @if ($enInicio) aria-current="page" @endif
           @class(['flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm',
                   'bg-marca-800 font-medium text-white' => $enInicio,
                   'hover:bg-white/10' => ! $enInicio])>
            <x-icono nombre="inicio" class="size-[18px]" />
            Inicio
            @if ($enInicio) <span class="ml-auto size-1.5 rounded-full bg-acento-400"></span> @endif
        </a>

        @foreach ($secciones as $seccion)
            <div class="flex flex-col gap-px">
                <span class="px-2.5 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-marca-300">{{ $seccion['titulo'] }}</span>
                @foreach ($seccion['items'] as $item)
                    @php $activo = request()->routeIs($item['activo']); @endphp
                    <a href="{{ route($item['ruta']) }}" @if ($activo) aria-current="page" @endif
                       @class(['flex items-center rounded-lg px-2.5 py-2 text-sm',
                               'bg-marca-800 font-medium text-white' => $activo,
                               'hover:bg-white/10' => ! $activo])>
                        {{ $item['label'] }}
                        @if ($activo) <span class="ml-auto size-1.5 rounded-full bg-acento-400"></span> @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="flex min-w-0 flex-1 flex-col">

        {{-- ===================== Barra superior ===================== --}}
        <header class="flex min-h-16 flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3 sm:px-8">
            <div class="flex items-center gap-3">
                <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                        @click="menu = ! menu" :aria-expanded="menu" aria-label="Abrir o cerrar el menú">
                    <x-icono nombre="menu" />
                </button>
                <nav aria-label="Ruta" class="text-sm text-slate-500">@yield('migas', 'Inicio')</nav>
            </div>

            {{-- Menú del usuario: cambiar contraseña y salir --}}
            <div x-data="{ abierto: false }" class="relative" @keydown.escape="abierto = false">
                <button type="button" @click="abierto = ! abierto" :aria-expanded="abierto" aria-haspopup="menu"
                        class="flex items-center gap-2.5 rounded-xl p-1 text-left hover:bg-slate-50">
                    <span class="flex size-9 items-center justify-center rounded-full bg-marca-100 text-[13px] font-semibold text-marca-900">{{ Etiquetas::iniciales($yo->nombre) }}</span>
                    <span class="hidden sm:block">
                        <span class="block text-sm font-medium text-slate-800">{{ $yo->nombre }}</span>
                        <span class="block text-xs text-slate-500">{{ Etiquetas::rol($yo->rol) }}</span>
                    </span>
                    <x-icono nombre="abajo" class="size-4 text-slate-500" />
                </button>

                <div x-show="abierto" x-cloak x-transition.origin.top.right @click.outside="abierto = false" role="menu"
                     class="absolute right-0 z-30 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                    <a href="{{ route('perfil.password.edit') }}" role="menuitem"
                       class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm hover:bg-slate-50">
                        <x-icono nombre="llave" class="size-[18px] text-slate-500" /> Cambiar contraseña
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" role="menuitem"
                                class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm hover:bg-slate-50">
                            <x-icono nombre="salir" class="size-[18px] text-slate-500" /> Salir
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- ===================== Contenido ===================== --}}
        <main class="flex flex-col gap-5 p-4 sm:p-8">

            @if (session('ok'))
                <div x-data="{ visible: true }" x-show="visible" role="status"
                     class="flex items-start gap-2.5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    <x-icono nombre="check" class="mt-0.5 size-[18px]" />
                    <span class="flex-1">{{ session('ok') }}</span>
                    <button type="button" @click="visible = false" aria-label="Cerrar mensaje" class="text-green-700 hover:text-green-900">
                        <x-icono nombre="cerrar" class="size-4" />
                    </button>
                </div>
            @endif

            {{-- USA02: reglas de negocio incumplidas. 'negocio' = ReglaNegocioException (modelos y controladores) --}}
            @foreach (['negocio', 'usuario', 'confirmacion'] as $campo)
                @error($campo)
                    <div role="alert" class="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <x-icono nombre="error" class="mt-0.5 size-[18px]" />
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            @endforeach

            @yield('contenido')
        </main>
    </div>
</div>

{{-- Ventana de confirmación compartida (inactivar, bloquear...): se abre con $dispatch('confirmar', {...}) --}}
<x-modal-confirmacion />

{{-- ===================== CUS-004: contraseña temporal (una sola vez) ===================== --}}
{{-- ===================== CUS-004: contraseña temporal (una sola vez) ===================== --}}
{{-- CAMBIO CLAVE: Solo entra si $clave existe Y es un array --}}
@if (isset($clave) && is_array($clave))
    <div x-data="{ abierto: true, copiada: false }" x-show="abierto" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-6"
         x-init="$nextTick(() => $refs.copiar.focus())">
        <div role="dialog" aria-modal="true" aria-labelledby="titulo-clave"
             class="flex w-full max-w-[480px] flex-col gap-5 rounded-2xl bg-white p-7 shadow-xl">
            <div class="flex items-start gap-3.5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
                    <x-icono nombre="check" class="size-[22px]" />
                </span>
                <div>
                    <h2 id="titulo-clave" class="text-lg font-semibold tracking-tight">{{ $clave['titulo'] ?? '' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $clave['usuario'] ?? '' }}</p>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-[13px] font-medium text-slate-500">Contraseña temporal</span>
                <div class="flex items-stretch gap-2">
                    <code x-ref="clave" class="flex h-[54px] flex-1 select-all items-center justify-center rounded-[10px] border border-dashed border-slate-400 bg-fondo font-mono text-[22px] tracking-[0.12em] text-marca-900">{{ $clave['clave'] ?? '' }}</code>
                    <button type="button" x-ref="copiar"
                            @click="navigator.clipboard.writeText($refs.clave.textContent.trim()); copiada = true"
                            class="inline-flex items-center gap-1.5 rounded-[10px] border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50">
                        <x-icono nombre="copiar" class="size-[18px]" />
                        <span x-text="copiada ? 'Copiada' : 'Copiar'">Copiar</span>
                    </button>
                </div>
            </div>

            <div class="flex items-start gap-2.5 rounded-[10px] border border-yellow-200 bg-yellow-50 p-3 text-sm leading-relaxed text-yellow-900">
                <x-icono nombre="alerta" class="mt-0.5 size-[18px]" />
                <span>Se muestra <strong>una sola vez</strong>. Entréguela en persona y de forma confidencial. Deberá cambiarse en el primer ingreso.</span>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="abierto = false"
                        class="h-11 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700">
                    Listo, ya la copié
                </button>
            </div>
        </div>
    </div>
@endif
</body>
</html>

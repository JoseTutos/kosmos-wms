{{--
    Kosmos WMS · Layout · Pantallas fuera de la aplicación (croquis 1 y 2)
    Lo usan: auth/login y perfil/password.

    Cumple:
      - An. §3.1: Vista = Blade + Tailwind. Sin reglas de negocio.
      - Diseño aprobado: pantalla dividida con panel de marca, o fondo blanco completo.

    Secciones que define cada vista:
      @section('titulo')  título de la pestaña
      @section('lema')    frase grande del panel azul
      @section('sublema') frase pequeña del panel azul
      @section('contenido') el formulario
--}}
@php
    $conPanel = (bool) config('kosmos.login.panel', true);
    $fondo    = config('kosmos.login.fondo'); // ruta relativa a public/, o null
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titulo', 'Ingresar') · Kosmos WMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-800 antialiased">
<div class="flex min-h-screen">

    {{-- ================================================================
         ZONA DE IMAGEN DE FONDO
         - Encender/apagar el panel:  KOSMOS_LOGIN_PANEL=true|false  (.env)
         - Poner una foto:            KOSMOS_LOGIN_FONDO=img/login-fondo.jpg
           (archivo en public/img/; recomendado JPG/WebP ≤ 300 KB, 1600 px de ancho)
         - Sin foto se ve el azul de marca. Con foto se le pone un velo azul
           para que el texto siempre se lea.
         - En pantallas pequeñas el panel se oculta y queda solo el formulario.
         ================================================================ --}}
    @if ($conPanel)
        <aside class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-marca-900 bg-cover bg-center p-10 text-white lg:flex"
               @if ($fondo) style="background-image: url('{{ asset($fondo) }}')" @endif>
            @if ($fondo)
                <div class="absolute inset-0 bg-marca-900/70" aria-hidden="true"></div>
            @endif

            <div class="relative flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-[10px] bg-acento-600 text-xl font-bold">K</span>
                <span class="text-lg font-semibold tracking-tight">Kosmos WMS</span>
            </div>

            <div class="relative max-w-md">
                <p class="text-3xl font-semibold leading-tight tracking-tight">@yield('lema', 'Control de bodega para Zona Franca')</p>
                <p class="mt-3 text-[15px] leading-relaxed text-slate-300">@yield('sublema', 'Recepción, legalización y despacho con trazabilidad completa.')</p>
            </div>
        </aside>
    @endif

    <main class="flex flex-1 items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-sm">
            {{-- Logo: siempre sin panel; con panel, solo en pantallas pequeñas --}}
            <div @class(['mb-8 flex items-center gap-3', 'lg:hidden' => $conPanel])>
                <span class="flex size-10 items-center justify-center rounded-[10px] bg-marca-900 text-xl font-bold text-acento-400">K</span>
                <span class="text-lg font-semibold tracking-tight">Kosmos WMS</span>
            </div>

            @yield('contenido')
        </div>
    </main>
</div>
</body>
</html>

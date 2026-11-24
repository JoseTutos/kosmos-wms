{{--
    Kosmos WMS · Vista · Ingreso web (croquis 1) — LoginController@create / store

    Cumple:
      - SEG01: autenticación por sesión para los roles administrativos.
      - CUS-004: los mensajes de credenciales, bloqueo y canal los genera
        AutenticacionService y llegan en el error 'email'. La vista no decide nada.

    Seguridad:
      - @csrf en el formulario. La contraseña nunca se vuelve a pintar tras un error; el correo sí (old).
      - autocomplete correcto (username / current-password) para gestores de contraseñas.
      - Sin "recordarme" (la tabla no tiene remember_token) ni recuperación por correo (fuera del MVP).
      - El botón de mostrar la contraseña solo cambia el tipo del campo en el navegador.
--}}
@extends('layouts.guest')

@section('titulo', 'Ingresar')

@section('contenido')
    <h1 class="text-[26px] font-semibold tracking-tight">Bienvenido</h1>
    <p class="mt-1.5 text-[15px] text-slate-600">Ingrese con su correo corporativo.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-7 flex flex-col gap-[18px]" novalidate>
        @csrf

        @error('email')
            <div role="alert" class="flex items-start gap-2.5 rounded-[10px] border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-800">
                <x-icono nombre="error" class="mt-px size-[18px]" />
                <span>{{ $message }}</span>
            </div>
        @enderror

        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium">Correo</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username" maxlength="150" inputmode="email"
                   @error('email') aria-invalid="true" @enderror
                   class="h-[46px] rounded-[10px] border border-slate-300 px-3.5 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
        </div>

        <div x-data="{ ver: false }" class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium">Contraseña</label>
            <div class="relative">
                <input id="password" name="password" :type="ver ? 'text' : 'password'" type="password" required
                       autocomplete="current-password" maxlength="255"
                       class="h-[46px] w-full rounded-[10px] border border-slate-300 pl-3.5 pr-12 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
                <button type="button" @click="ver = ! ver" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        aria-label="Mostrar contraseña"
                        class="absolute right-px top-px flex size-11 items-center justify-center text-slate-500 hover:text-slate-700">
                    <x-icono nombre="ojo" class="size-5" x-show="! ver" />
                    <x-icono nombre="ojo-off" class="size-5" x-show="ver" x-cloak />
                </button>
            </div>
        </div>

        <button type="submit" class="h-[46px] rounded-[10px] bg-marca-600 text-[15px] font-semibold text-white hover:bg-marca-700">
            Ingresar
        </button>
    </form>

    <p class="mt-7 text-[13px] leading-relaxed text-slate-500">
        ¿Olvidó su contraseña o su cuenta está bloqueada? Contacte al Administrador del Sistema.
    </p>
@endsection

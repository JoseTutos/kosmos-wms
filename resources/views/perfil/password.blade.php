{{--
    Kosmos WMS · Vista · Definir / cambiar contraseña (croquis 2) — PerfilController@editPassword / updatePassword

    Dos casos, la misma pantalla:
      - Clave temporal (debe_cambiar_password = true): el middleware password.changed trae aquí
        cualquier otra pantalla. No hay menú; solo "Salir".
      - Cambio voluntario desde el menú del usuario: aparece "Volver al inicio".

    Cumple:
      - Decisión de diseño (CUS-004): solo el usuario conoce su contraseña definitiva.
      - Reglas de CambiarPasswordRequest (mínimo 8, letras y números, máximo 72, distinta de
        la actual). La lista de reglas se actualiza mientras se escribe (Alpine) como ayuda;
        el servidor vuelve a validar todo.

    Seguridad: @csrf + @method('PUT'); autocomplete current-password / new-password;
    la salida es un formulario POST (logout con token CSRF), nunca un enlace GET.
--}}
@extends('layouts.guest')

@php $temporal = auth()->user()->debe_cambiar_password; @endphp

@section('titulo', $temporal ? 'Defina su contraseña' : 'Cambiar contraseña')
@section('lema', 'Su cuenta, solo suya')
@section('sublema', 'Cada movimiento queda registrado a su nombre. Por eso la contraseña la define usted.')

@section('contenido')
    <h1 class="text-[26px] font-semibold tracking-tight">{{ $temporal ? 'Defina su contraseña' : 'Cambiar contraseña' }}</h1>
    <p class="mt-1.5 text-[15px] text-slate-600">
        Hola, {{ \Illuminate\Support\Str::before(auth()->user()->nombre, ' ') }}.
        {{ $temporal ? 'Es su primer ingreso o su clave fue restablecida.' : 'Elija una contraseña nueva.' }}
    </p>

    @if ($temporal)
        <div class="mt-6 flex items-start gap-2.5 rounded-[10px] border border-yellow-200 bg-yellow-50 px-3 py-2.5 text-sm text-yellow-900">
            <x-icono nombre="llave" class="mt-px size-[18px]" />
            <span>Está usando la contraseña temporal que le entregó el Administrador del Sistema.</span>
        </div>
    @endif

    <form method="POST" action="{{ route('perfil.password.update') }}" class="mt-6 flex flex-col gap-4"
          x-data="{ nueva: '', actual: '' }" novalidate>
        @csrf
        @method('PUT')

        <div class="flex flex-col gap-1.5">
            <label for="password_actual" class="text-sm font-medium">{{ $temporal ? 'Contraseña temporal' : 'Contraseña actual' }}</label>
            <input id="password_actual" name="password_actual" type="password" required autocomplete="current-password"
                   maxlength="255" x-model="actual" @error('password_actual') aria-invalid="true" @enderror
                   class="h-[46px] rounded-[10px] border border-slate-300 px-3.5 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            @error('password_actual') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium">Nueva contraseña</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   minlength="8" maxlength="72" x-model="nueva" aria-describedby="reglas-password"
                   @error('password') aria-invalid="true" @enderror
                   class="h-[46px] rounded-[10px] border border-slate-300 px-3.5 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            <ul id="reglas-password" class="mt-1 flex flex-wrap gap-x-3.5 gap-y-1.5 text-[13px]">
                <li class="flex items-center gap-1.5" :class="nueva.length >= 8 ? 'text-green-700' : 'text-slate-500'">
                    <x-icono nombre="check" class="size-[15px]" x-show="nueva.length >= 8" />
                    <x-icono nombre="circulo" class="size-[15px]" x-show="nueva.length < 8" />
                    8 o más caracteres
                </li>
                <li class="flex items-center gap-1.5" :class="/[a-zA-Z]/.test(nueva) && /\d/.test(nueva) ? 'text-green-700' : 'text-slate-500'">
                    <x-icono nombre="check" class="size-[15px]" x-show="/[a-zA-Z]/.test(nueva) && /\d/.test(nueva)" />
                    <x-icono nombre="circulo" class="size-[15px]" x-show="! (/[a-zA-Z]/.test(nueva) && /\d/.test(nueva))" />
                    Letras y números
                </li>
                <li class="flex items-center gap-1.5" :class="nueva && nueva !== actual ? 'text-green-700' : 'text-slate-500'">
                    <x-icono nombre="check" class="size-[15px]" x-show="nueva && nueva !== actual" />
                    <x-icono nombre="circulo" class="size-[15px]" x-show="! (nueva && nueva !== actual)" />
                    Distinta de la actual
                </li>
            </ul>
            @error('password') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="password_confirmation" class="text-sm font-medium">Confirmar nueva contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   maxlength="72"
                   class="h-[46px] rounded-[10px] border border-slate-300 px-3.5 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
        </div>

        <button type="submit" class="h-[46px] rounded-[10px] bg-marca-600 text-[15px] font-semibold text-white hover:bg-marca-700">
            {{ $temporal ? 'Guardar y continuar' : 'Guardar contraseña' }}
        </button>
    </form>

    <div class="mt-6 flex items-center justify-between text-[13px] text-slate-500">
        @if ($temporal)
            <span>No podrá continuar sin definirla.</span>
        @else
            <a href="{{ route('dashboard') }}" class="text-marca-600 hover:text-marca-900">Volver al inicio</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-marca-600 hover:text-marca-900">Salir</button>
        </form>
    </div>
@endsection

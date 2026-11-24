{{--
    Kosmos WMS · Vista · CUS-004 Editar usuario — UsuarioController@edit / update

    Cumple:
      - CUS-004 / RS-19: cambio de nombre, correo y rol (un solo rol).

    Restricciones:
      - Cambiar el rol revoca la sesión del usuario en la app (lo hace el controlador).
      - Un ADMIN_SISTEMA no puede cambiarse su propio rol ni quitárselo al último
        administrador activo: el controlador lo rechaza y el mensaje aparece arriba (USA02).
      - El estado y la contraseña se gestionan desde el listado, con su confirmación.

    Seguridad: @csrf + @method('PUT').
--}}
@extends('layouts.app')

@section('titulo', 'Editar usuario')
@section('migas')
    Administración <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('usuarios.index') }}" class="text-marca-600 hover:text-marca-900">Usuarios</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Editar</span>
@endsection

@section('contenido')
    <h1 class="text-2xl font-semibold tracking-tight">Editar usuario</h1>

    <form method="POST" action="{{ route('usuarios.update', $usuario) }}"
          class="flex max-w-[760px] flex-col gap-6 rounded-[14px] border border-slate-200 bg-white p-7">
        @csrf
        @method('PUT')
        @include('usuarios._form')

        <div class="flex items-start gap-2.5 rounded-[10px] bg-marca-50 px-3.5 py-3 text-sm text-slate-700">
            <x-icono nombre="info" class="mt-px size-[18px] text-marca-600" />
            <span>Si cambia el rol, el usuario deberá volver a ingresar en la app móvil. El estado y la contraseña se gestionan desde el listado.</span>
        </div>

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="{{ route('usuarios.index') }}" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Guardar cambios</button>
        </div>
    </form>
@endsection

{{--
    Kosmos WMS · Vista · CUS-004 Nuevo usuario (croquis 5) — UsuarioController@create / store

    Cumple:
      - CUS-004: alta con un rol; el usuario nace ACTIVO (valores por defecto del modelo).
      - Decisión de diseño: al guardar, el sistema genera la contraseña temporal y la muestra
        UNA vez en ventana modal (layouts/app), de vuelta en el listado.

    Seguridad: @csrf; el formulario no envía contraseña ni estado.
--}}
@extends('layouts.app')

@section('titulo', 'Nuevo usuario')
@section('migas')
    Administración <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('usuarios.index') }}" class="text-marca-600 hover:text-marca-900">Usuarios</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Nuevo</span>
@endsection

@section('contenido')
    <h1 class="text-2xl font-semibold tracking-tight">Nuevo usuario</h1>

    <form method="POST" action="{{ route('usuarios.store') }}"
          class="flex max-w-[760px] flex-col gap-6 rounded-[14px] border border-slate-200 bg-white p-7">
        @csrf
        @include('usuarios._form')

        <div class="flex items-start gap-2.5 rounded-[10px] bg-marca-50 px-3.5 py-3 text-sm text-slate-700">
            <x-icono nombre="info" class="mt-px size-[18px] text-marca-600" />
            <span>Al guardar, el sistema genera una contraseña temporal y se la muestra una sola vez. El usuario deberá cambiarla en su primer ingreso.</span>
        </div>

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="{{ route('usuarios.index') }}" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Crear usuario</button>
        </div>
    </form>
@endsection

{{--
    Kosmos WMS · Vista · CUS-001 Nuevo cliente — ClienteController@create / store
    Seguridad: @csrf. El cliente nace ACTIVO (valor por defecto del modelo); el formulario no envía estado.
--}}
@extends('layouts.app')

@section('titulo', 'Nuevo cliente')
@section('migas')
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('clientes.index') }}" class="text-marca-600 hover:text-marca-900">Clientes</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Nuevo</span>
@endsection

@section('contenido')
    <h1 class="text-2xl font-semibold tracking-tight">Nuevo cliente</h1>

    <form method="POST" action="{{ route('clientes.store') }}" class="flex max-w-[760px] flex-col gap-5 rounded-[14px] border border-slate-200 bg-white p-7">
        @csrf
        @include('clientes._form')

        <div class="flex items-start gap-2.5 rounded-[10px] bg-yellow-50 px-3.5 py-3 text-sm text-yellow-900">
            <x-icono nombre="alerta" class="mt-px size-[18px]" />
            <span>Revise el NIT y el código corto antes de guardar: después no se pueden cambiar.</span>
        </div>

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="{{ route('clientes.index') }}" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Crear cliente</button>
        </div>
    </form>
@endsection

{{--
    Kosmos WMS · Vista · CUS-001 Editar cliente — ClienteController@edit / update
    NIT y código corto en solo lectura (inmutables). Seguridad: @csrf + @method('PUT').
--}}
@extends('layouts.app')

@section('titulo', 'Editar cliente')
@section('migas')
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('clientes.index') }}" class="text-marca-600 hover:text-marca-900">Clientes</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">{{ $cliente->codigo_corto }}</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">{{ $cliente->razon_social }}</h1>
        <x-estado :activo="$cliente->estaActivo()" :texto="\App\Support\Etiquetas::activo($cliente->estado)" />
    </div>

    <form method="POST" action="{{ route('clientes.update', $cliente) }}" class="flex max-w-[760px] flex-col gap-5 rounded-[14px] border border-slate-200 bg-white p-7">
        @csrf
        @method('PUT')
        @include('clientes._form')

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="{{ route('clientes.index') }}" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Guardar cambios</button>
        </div>
    </form>
@endsection

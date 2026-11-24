{{--
    Kosmos WMS · Vista · CUS-003 Editar bodega — BodegaController@edit / update
    Código en solo lectura (inmutable). Seguridad: @csrf + @method('PUT').
    Apagar las ubicaciones internas con pallets ubicados lo rechaza el controlador (mensaje arriba).
--}}
@extends('layouts.app')

@section('titulo', 'Editar bodega')
@section('migas')
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('bodegas.index') }}" class="text-marca-600 hover:text-marca-900">Bodegas</a>
    <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('bodegas.show', $bodega) }}" class="text-marca-600 hover:text-marca-900">{{ $bodega->codigo }}</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Editar</span>
@endsection

@section('contenido')
    <h1 class="text-2xl font-semibold tracking-tight">Editar {{ $bodega->nombre }}</h1>

    <form method="POST" action="{{ route('bodegas.update', $bodega) }}" class="flex max-w-[760px] flex-col gap-5 rounded-[14px] border border-slate-200 bg-white p-7">
        @csrf
        @method('PUT')
        @include('bodegas._form')

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="{{ route('bodegas.show', $bodega) }}" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Guardar cambios</button>
        </div>
    </form>
@endsection

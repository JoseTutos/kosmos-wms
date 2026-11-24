{{--
    Kosmos WMS · Vista · CUS-003 Nueva bodega — BodegaController@create / store
    La bodega nace ACTIVA y con 0 m² ocupados (valores por defecto del modelo). Seguridad: @csrf.
--}}
@extends('layouts.app')

@section('titulo', 'Nueva bodega')
@section('migas')
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('bodegas.index') }}" class="text-marca-600 hover:text-marca-900">Bodegas</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Nueva</span>
@endsection

@section('contenido')
    <h1 class="text-2xl font-semibold tracking-tight">Nueva bodega</h1>

    <form method="POST" action="{{ route('bodegas.store') }}" class="flex max-w-[760px] flex-col gap-5 rounded-[14px] border border-slate-200 bg-white p-7">
        @csrf
        @include('bodegas._form')

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="{{ route('bodegas.index') }}" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Crear bodega</button>
        </div>
    </form>
@endsection

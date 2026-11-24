{{--
    Kosmos WMS · Vista · CUS-002 Nuevo producto — ProductoController@create / store
    El producto nace ACTIVO y con SKU automático (modelo). Seguridad: @csrf.
--}}
@extends('layouts.app')

@section('titulo', 'Nuevo producto')
@section('migas')
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('productos.index') }}" class="text-marca-600 hover:text-marca-900">Productos</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Nuevo</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">Nuevo producto</h1>
        <a href="{{ route('productos.importar') }}" class="text-sm text-marca-600 hover:text-marca-900">¿Son muchos? Impórtelos desde un CSV</a>
    </div>

    @if ($clientes->isEmpty())
        <div class="flex max-w-[760px] items-start gap-2.5 rounded-[14px] border border-yellow-200 bg-yellow-50 p-5 text-sm text-yellow-900">
            <x-icono nombre="alerta" class="mt-px size-[18px]" />
            <span>No hay clientes activos. <a href="{{ route('clientes.create') }}" class="font-medium underline">Registre un cliente</a> antes de crear productos.</span>
        </div>
    @else
        <form method="POST" action="{{ route('productos.store') }}" class="flex max-w-[760px] flex-col gap-5 rounded-[14px] border border-slate-200 bg-white p-7">
            @csrf
            @include('productos._form')

            <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
                <a href="{{ route('productos.index') }}" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
                <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Crear producto</button>
            </div>
        </form>
    @endif
@endsection

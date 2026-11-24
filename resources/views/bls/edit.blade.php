@extends('layouts.app')

@section('titulo', 'Editar BL '.$bl->numero_bl)

@section('migas')
    Entrada <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('bls.index') }}" class="hover:underline">BL</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="{{ route('bls.show', $bl) }}" class="hover:underline">{{ $bl->numero_bl }}</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Editar</span>
@endsection

@section('contenido')
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="font-mono text-2xl font-semibold tracking-tight">{{ $bl->numero_bl }}</h1>
        <x-estado-bl :estado="$bl->estado" />
    </div>

    <form method="POST" action="{{ route('bls.update', $bl) }}" enctype="multipart/form-data"
          class="flex flex-col gap-6 rounded-[14px] border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        @include('bls._form')

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="{{ route('bls.show', $bl) }}" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm font-medium hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700">Guardar cambios</button>
        </div>
    </form>
@endsection

@extends('layouts.app')

@use('Illuminate\Support\Js')

@section('titulo', $legalizacion->consecutivo)

{{-- La usan CUS-010 (legalizaciones.show) y CUS-012 (historicos.legalizaciones.show, con $historico = true) --}}
@section('migas')
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    @if ($historico ?? false)
        <a href="{{ route('historicos.legalizaciones.index') }}" class="hover:underline">Históricos</a> <span class="mx-1.5" aria-hidden="true">›</span>
    @else
        <a href="{{ route('legalizaciones.index') }}" class="hover:underline">Legalizaciones</a> <span class="mx-1.5" aria-hidden="true">›</span>
    @endif
    <span class="font-medium text-slate-800">{{ $legalizacion->consecutivo }}</span>
@endsection

@section('contenido')
    @php
        $seriales = $legalizacion->detalles->sum(fn ($d) => $d->seriales->count());
        $prefijo = ($historico ?? false) ? 'historicos.legalizaciones' : 'legalizaciones';
    @endphp

    {{-- L10: la legalización quedó registrada; se borra el carrito guardado en este navegador --}}
    @if (session('limpiar_carrito'))
        <div hidden x-data x-init="try { localStorage.removeItem({{ Js::from('kosmos.legalizacion.bl.'.session('limpiar_carrito')) }}) } catch (e) {}"></div>
    @endif

    @if (session('aviso'))
        <div role="status" class="flex items-start gap-2.5 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
            <x-icono nombre="alerta" class="mt-0.5 size-[18px]" />
            <span>{{ session('aviso') }}</span>
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-mono text-2xl font-semibold tracking-tight">{{ $legalizacion->consecutivo }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                BL <span class="font-mono">{{ $legalizacion->bl->numero_bl }}</span>
                · {{ $legalizacion->bl->cliente->codigo_corto }} · {{ $legalizacion->bl->cliente->razon_social }}
            </p>
        </div>
        <a href="{{ route($prefijo.'.soporte', $legalizacion) }}"
           class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
            <x-icono nombre="abajo" class="size-[18px]" /> Descargar soporte
        </a>
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Documento</p>
            <p class="mt-1 font-semibold">{{ $legalizacion->tipo_documento->etiqueta() }}</p>
            <p class="font-mono text-sm">{{ $legalizacion->numero_autorizacion }}</p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Fecha de autorización</p>
            <p class="mt-1 text-xl font-semibold tabular-nums">{{ $legalizacion->fecha_autorizacion->format('d/m/Y') }}</p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Registró</p>
            <p class="mt-1 font-semibold">{{ $legalizacion->usuario->nombre }}</p>
            <p class="text-sm tabular-nums text-slate-500">{{ $legalizacion->created_at?->format('d/m/Y H:i') }}</p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Líneas · seriales</p>
            <p class="mt-1 text-xl font-semibold tabular-nums">{{ $legalizacion->detalles->count() }} <span class="text-base font-normal text-slate-500">· {{ $seriales }}</span></p>
        </div>
    </div>

    @if ($otrosUsos->isNotEmpty())
        <div class="flex items-start gap-2.5 rounded-[14px] border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900">
            <x-icono nombre="info" class="mt-px size-[18px]" />
            <span>
                Este número de autorización también aparece en:
                @foreach ($otrosUsos as $uso)
                    <a href="{{ route($prefijo.'.show', $uso) }}" class="font-mono font-semibold hover:underline">{{ $uso->consecutivo }}</a>{{ $loop->last ? '.' : ',' }}
                @endforeach
            </span>
        </div>
    @endif

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-[15px] font-semibold">Detalle legalizado</h2>
            <p class="text-xs text-slate-500">Registro de solo lectura. Para corregir un error registre un ajuste de inventario.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Bulto</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Pallet · Bodega</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Producto</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Cantidad</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Seriales</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($legalizacion->detalles as $detalle)
                    @php
                        $linea = $detalle->bultoDetalle;
                    @endphp
                    <tr class="border-t border-slate-100 align-top">
                        <td class="px-4 py-3 font-mono text-[13px] font-semibold">{{ $linea->bulto->codigo }}</td>
                        <td class="px-4 py-3 font-mono text-[13px]">{{ $linea->bulto->pallet?->codigo }} · {{ $linea->bulto->pallet?->bodega?->codigo }}</td>
                        <td class="px-4 py-3" title="{{ $linea->producto->descripcion }}">{{ $linea->producto->sku }} <span class="text-xs text-slate-500">{{ $linea->producto->unidad_medida->value }}</span></td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ $detalle->cantidad }}</td>
                        <td class="px-4 py-3">
                            @if ($detalle->seriales->isEmpty())
                                <span class="text-slate-400">—</span>
                            @else
                                <details>
                                    <summary class="cursor-pointer text-marca-600">{{ $detalle->seriales->count() }} seriales</summary>
                                    <ul class="mt-2 grid grid-cols-2 gap-x-4 gap-y-0.5 font-mono text-xs">
                                        @foreach ($detalle->seriales as $fila)
                                            <li>{{ $fila->serial->codigo }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Esta legalización no tiene líneas.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

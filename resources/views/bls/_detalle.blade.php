{{-- Manifiesto del BL (solo lectura). Variable: $bl con detalles.producto cargados. --}}
@use('App\Support\Milesimas')

@php
    $totalBultos = $bl->detalles->sum('bultos_esperados');
    $totalPeso = Milesimas::texto($bl->detalles->sum(fn ($d) => Milesimas::de($d->peso_bruto_kg)));
@endphp

<div class="rounded-[14px] border border-slate-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
        <h2 class="text-base font-semibold">Manifiesto</h2>
        <span class="text-xs text-slate-500">{{ $bl->detalles->count() }} {{ $bl->detalles->count() === 1 ? 'producto' : 'productos' }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[700px] text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th scope="col" class="px-4 py-3 font-semibold">SKU</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Descripción</th>
                    <th scope="col" class="px-4 py-3 text-right font-semibold">Bultos</th>
                    <th scope="col" class="px-4 py-3 text-right font-semibold">Unidades</th>
                    <th scope="col" class="px-4 py-3 text-right font-semibold">Peso bruto (kg)</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($bl->detalles as $d)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3 font-mono text-[13px] font-semibold">{{ $d->producto->sku }}</td>
                    <td class="px-4 py-3">{{ $d->producto->descripcion }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $d->bultos_esperados }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $d->unidades_esperadas }} <span class="text-xs text-slate-500">{{ $d->producto->unidad_medida->value }}</span></td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $d->peso_bruto_kg }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">El BL no tiene productos en el manifiesto.</td>
                </tr>
            @endforelse
            </tbody>
            @if ($bl->detalles->isNotEmpty())
                <tfoot>
                    <tr class="border-t border-slate-200 bg-fondo font-semibold">
                        <td colspan="2" class="px-4 py-3">Total</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $totalBultos }}</td>
                        <td class="px-4 py-3 text-right text-xs font-normal text-slate-500">por unidad de cada producto</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $totalPeso }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

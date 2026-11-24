{{--
    Ocupación de una bodega (RS-15, D9): m2_ocupados / tamano_m2.
    Uso: @include('bodegas._ocupacion', ['bodega' => $bodega])   (dentro de un @foreach basta con @include('bodegas._ocupacion'))
    Más de 100 % es válido: se muestra con alerta (decisión de Maestros).
--}}
@php
    $porcentaje = (float) $bodega->porcentaje_ocupacion;
    $ancho      = min(100, max(0, $porcentaje));
    $sobrecupo  = $porcentaje > 100;
    $alto       = ! $sobrecupo && $porcentaje >= 80;

    $colorBarra = $sobrecupo ? 'bg-red-600' : ($alto ? 'bg-acento-500' : 'bg-marca-600');
    $colorTexto = $sobrecupo ? 'text-red-700' : ($alto ? 'text-acento-700' : 'text-slate-700');
@endphp

<div class="flex min-w-[140px] flex-col gap-1">
    <div class="flex items-baseline justify-between gap-2 text-xs">
        <span class="font-semibold {{ $colorTexto }}">{{ number_format($porcentaje, 1, ',', '.') }} %</span>
        <span class="text-slate-500">
            {{ number_format((float) $bodega->m2_ocupados, 2, ',', '.') }} / {{ number_format((float) $bodega->tamano_m2, 2, ',', '.') }} m²
        </span>
    </div>

    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200"
         role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($ancho) }}"
         aria-label="Ocupación de la bodega {{ $bodega->codigo }}">
        <div class="h-full rounded-full {{ $colorBarra }}" style="width: {{ $ancho }}%"></div>
    </div>

    @if ($sobrecupo)
        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-red-700" role="status">
            <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>
            </svg>
            Sobrecupo
        </span>
    @endif
</div>

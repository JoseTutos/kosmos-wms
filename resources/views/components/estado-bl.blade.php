{{--
    Insignia del estado del BL (3.1.2).
    Uso: <x-estado-bl :estado="$bl->estado" />
--}}
@props(['estado'])

@php
    $valor = $estado instanceof \BackedEnum ? $estado->value : (string) $estado;

    [$texto, $clases, $punto] = match ($valor) {
        'PROGRAMADO'         => ['Programado', 'bg-marca-100 text-marca-900', 'bg-marca-600'],
        'EN_RECEPCION'       => ['En recepción', 'bg-acento-100 text-acento-800', 'bg-acento-500'],
        'RECIBIDO'           => ['Recibido', 'bg-green-100 text-green-800', 'bg-green-600'],
        'DESPACHADO_PARCIAL' => ['Despacho parcial', 'bg-yellow-100 text-yellow-900', 'bg-yellow-500'],
        'CERRADO'            => ['Cerrado', 'bg-slate-200 text-slate-700', 'bg-slate-500'],
        default              => [$valor, 'bg-slate-100 text-slate-700', 'bg-slate-400'],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {$clases}"]) }}>
    <span class="size-1.5 rounded-full {{ $punto }}"></span>
    {{ $texto }}
</span>

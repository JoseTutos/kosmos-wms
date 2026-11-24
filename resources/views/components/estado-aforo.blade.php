{{--
    Insignia del estado del aforo.
    Uso: <x-estado-aforo :estado="$aforo->estado" />
--}}
@props(['estado'])

@php
    $valor = $estado instanceof \BackedEnum ? $estado->value : (string) $estado;

    [$texto, $clases, $punto] = match ($valor) {
        'EN_PROCESO' => ['En proceso', 'bg-acento-100 text-acento-800', 'bg-acento-500'],
        'CERRADO'    => ['Cerrado', 'bg-green-100 text-green-800', 'bg-green-600'],
        'CANCELADO'  => ['Cancelado', 'bg-slate-200 text-slate-700', 'bg-slate-500'],
        default      => [$valor, 'bg-slate-100 text-slate-700', 'bg-slate-400'],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {$clases}"]) }}>
    <span class="size-1.5 rounded-full {{ $punto }}"></span>
    {{ $texto }}
</span>


<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['estado']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['estado']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $valor = $estado instanceof \BackedEnum ? $estado->value : (string) $estado;

    [$texto, $clases, $punto] = match ($valor) {
        'PROGRAMADO'         => ['Programado', 'bg-marca-100 text-marca-900', 'bg-marca-600'],
        'EN_RECEPCION'       => ['En recepción', 'bg-acento-100 text-acento-800', 'bg-acento-500'],
        'RECIBIDO'           => ['Recibido', 'bg-green-100 text-green-800', 'bg-green-600'],
        'DESPACHADO_PARCIAL' => ['Despacho parcial', 'bg-yellow-100 text-yellow-900', 'bg-yellow-500'],
        'CERRADO'            => ['Cerrado', 'bg-slate-200 text-slate-700', 'bg-slate-500'],
        default              => [$valor, 'bg-slate-100 text-slate-700', 'bg-slate-400'],
    };
?>

<span <?php echo e($attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {$clases}"])); ?>>
    <span class="size-1.5 rounded-full <?php echo e($punto); ?>"></span>
    <?php echo e($texto); ?>

</span>
<?php /**PATH E:\Kosmos\resources\views/components/estado-bl.blade.php ENDPATH**/ ?>
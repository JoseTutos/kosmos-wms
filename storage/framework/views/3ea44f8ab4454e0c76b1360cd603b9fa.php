
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['activo' => true, 'texto']));

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

foreach (array_filter((['activo' => true, 'texto']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold',
    'bg-green-100 text-green-800' => $activo,
    'bg-slate-200 text-slate-700' => ! $activo,
]); ?>">
    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['size-1.5 rounded-full', 'bg-green-600' => $activo, 'bg-slate-500' => ! $activo]); ?>"></span>
    <?php echo e($texto); ?>

</span>
<?php /**PATH E:\Kosmos\resources\views/components/estado.blade.php ENDPATH**/ ?>
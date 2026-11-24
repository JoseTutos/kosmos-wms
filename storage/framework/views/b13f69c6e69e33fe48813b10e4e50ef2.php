<?php $__env->startSection('titulo', 'BL '.$bl->numero_bl); ?>

<?php $__env->startSection('migas'); ?>
    Entrada <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('bls.index')); ?>" class="hover:underline">BL</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800"><?php echo e($bl->numero_bl); ?></span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-2xl font-semibold tracking-tight"><?php echo e($bl->numero_bl); ?></h1>
                <?php if (isset($component)) { $__componentOriginal13cfe373e4efdd5413e9190b757b2702 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal13cfe373e4efdd5413e9190b757b2702 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.estado-bl','data' => ['estado' => $bl->estado]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('estado-bl'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['estado' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bl->estado)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal13cfe373e4efdd5413e9190b757b2702)): ?>
<?php $attributes = $__attributesOriginal13cfe373e4efdd5413e9190b757b2702; ?>
<?php unset($__attributesOriginal13cfe373e4efdd5413e9190b757b2702); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal13cfe373e4efdd5413e9190b757b2702)): ?>
<?php $component = $__componentOriginal13cfe373e4efdd5413e9190b757b2702; ?>
<?php unset($__componentOriginal13cfe373e4efdd5413e9190b757b2702); ?>
<?php endif; ?>
            </div>
            <p class="mt-1 text-sm text-slate-500"><?php echo e($bl->cliente->codigo_corto); ?> · <?php echo e($bl->cliente->razon_social); ?></p>
        </div>

        <div class="flex flex-wrap gap-2.5">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $bl)): ?>
                <a href="<?php echo e(route('bls.edit', $bl)); ?>"
                   class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'editar','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'editar','class' => 'size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Editar
                </a>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $bl)): ?>
                <?php
                    $accion = [
                        'url' => route('bls.destroy', $bl), 'metodo' => 'DELETE', 'peligro' => true,
                        'titulo' => '¿Eliminar el BL '.$bl->numero_bl.'?',
                        'detalle' => $bl->cliente->codigo_corto.' · Factura '.$bl->factura_comercial,
                        'puntos' => ['Se borra el BL con su manifiesto.', 'Solo es posible porque aún no tiene bultos ni pallets.', 'La eliminación queda en la bitácora.'],
                        'boton' => 'Eliminar BL',
                    ];
                ?>
                <button type="button" @click="$dispatch('confirmar', <?php echo e(Js::from($accion)); ?>)"
                        class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-red-300 bg-white px-4 text-sm font-medium text-red-700 hover:bg-red-50">
                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'papelera','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'papelera','class' => 'size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Eliminar
                </button>
            <?php endif; ?>
        </div>
    </div>

    
    <dl class="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-4 rounded-[14px] border border-slate-200 bg-white p-5">
        <div>
            <dt class="text-xs font-medium text-slate-500">Fecha de ingreso</dt>
            <dd class="mt-1 text-sm font-medium tabular-nums"><?php echo e($bl->fecha_ingreso->format('d/m/Y')); ?></dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-slate-500">Factura comercial</dt>
            <dd class="mt-1 text-sm font-medium"><?php echo e($bl->factura_comercial); ?></dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-slate-500">NIT del cliente</dt>
            <dd class="mt-1 text-sm font-medium tabular-nums"><?php echo e($bl->cliente->nit); ?></dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-slate-500">Recepción</dt>
            <dd class="mt-1 text-sm font-medium tabular-nums"><?php echo e($bl->bultos_count); ?> bultos · <?php echo e($bl->pallets_count); ?> pallets</dd>
        </div>
        <?php if($bl->observacion_recepcion): ?>
            <div class="col-span-full">
                <dt class="text-xs font-medium text-slate-500">Observación de la recepción</dt>
                <dd class="mt-1 text-sm"><?php echo e($bl->observacion_recepcion); ?></dd>
            </div>
        <?php endif; ?>
    </dl>

    <?php echo $__env->make('bls._detalle', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-3">
            <h2 class="text-base font-semibold">Preinspección</h2>
            <p class="text-xs text-slate-500">Registro documental del trámite externo. Editable hasta que el BL quede recibido.</p>
        </div>

        <div class="flex flex-col gap-4 p-4">
            <div class="flex flex-wrap gap-x-8 gap-y-2 text-sm">
                <span><span class="text-slate-500">Fecha:</span> <?php echo e($bl->fecha_preinspeccion?->format('d/m/Y') ?? 'Sin registrar'); ?></span>
                <span>
                    <span class="text-slate-500">Acta:</span>
                    <?php if($bl->acta_preinspeccion_path): ?>
                        <a href="<?php echo e(route('bls.acta', $bl)); ?>" class="text-marca-600 hover:underline">Descargar</a>
                    <?php else: ?>
                        Sin acta
                    <?php endif; ?>
                </span>
            </div>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('preinspeccion', $bl)): ?>
                <form method="POST" action="<?php echo e(route('bls.preinspeccion', $bl)); ?>" enctype="multipart/form-data"
                      class="flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <div class="flex flex-col gap-1.5">
                        <label for="fecha_preinspeccion" class="text-xs font-medium text-slate-500">Fecha</label>
                        <input id="fecha_preinspeccion" name="fecha_preinspeccion" type="date" max="<?php echo e(now()->format('Y-m-d')); ?>"
                               value="<?php echo e(old('fecha_preinspeccion', $bl->fecha_preinspeccion?->format('Y-m-d'))); ?>"
                               class="h-10 rounded-[10px] border border-slate-300 px-3 text-sm">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="acta" class="text-xs font-medium text-slate-500">Acta (PDF o imagen, 10 MB)</label>
                        <input id="acta" name="acta" type="file" accept=".pdf,.jpg,.jpeg,.png"
                               class="rounded-[10px] border border-slate-300 bg-white p-1.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-sm">
                    </div>
                    <?php if($bl->acta_preinspeccion_path): ?>
                        <label class="inline-flex h-10 items-center gap-1.5 text-sm text-slate-600">
                            <input type="checkbox" name="quitar_acta" value="1" class="rounded border-slate-300"> Quitar acta
                        </label>
                    <?php endif; ?>
                    <button type="submit" class="h-10 rounded-[10px] bg-slate-800 px-4 text-sm font-medium text-white hover:bg-slate-700">Guardar preinspección</button>
                </form>
                <?php $__errorArgs = ['fecha_preinspeccion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['acta'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/bls/show.blade.php ENDPATH**/ ?>
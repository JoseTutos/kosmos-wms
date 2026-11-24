<?php $__env->startSection('titulo', 'Nuevo producto'); ?>
<?php $__env->startSection('migas'); ?>
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('productos.index')); ?>" class="text-marca-600 hover:text-marca-900">Productos</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Nuevo</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">Nuevo producto</h1>
        <a href="<?php echo e(route('productos.importar')); ?>" class="text-sm text-marca-600 hover:text-marca-900">¿Son muchos? Impórtelos desde un CSV</a>
    </div>

    <?php if($clientes->isEmpty()): ?>
        <div class="flex max-w-[760px] items-start gap-2.5 rounded-[14px] border border-yellow-200 bg-yellow-50 p-5 text-sm text-yellow-900">
            <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'alerta','class' => 'mt-px size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'alerta','class' => 'mt-px size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
            <span>No hay clientes activos. <a href="<?php echo e(route('clientes.create')); ?>" class="font-medium underline">Registre un cliente</a> antes de crear productos.</span>
        </div>
    <?php else: ?>
        <form method="POST" action="<?php echo e(route('productos.store')); ?>" class="flex max-w-[760px] flex-col gap-5 rounded-[14px] border border-slate-200 bg-white p-7">
            <?php echo csrf_field(); ?>
            <?php echo $__env->make('productos._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
                <a href="<?php echo e(route('productos.index')); ?>" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
                <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Crear producto</button>
            </div>
        </form>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/productos/create.blade.php ENDPATH**/ ?>
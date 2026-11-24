<?php $__env->startSection('titulo', 'Editar producto'); ?>
<?php $__env->startSection('migas'); ?>
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('productos.index')); ?>" class="text-marca-600 hover:text-marca-900">Productos</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-mono font-medium text-slate-800"><?php echo e($producto->sku); ?></span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight"><?php echo e($producto->descripcion); ?></h1>
        <?php if (isset($component)) { $__componentOriginald54ce5ecfb77cf9651bfc81950534c53 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald54ce5ecfb77cf9651bfc81950534c53 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.estado','data' => ['activo' => $producto->estaActivo(),'texto' => \App\Support\Etiquetas::activo($producto->estado)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('estado'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['activo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($producto->estaActivo()),'texto' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Support\Etiquetas::activo($producto->estado))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald54ce5ecfb77cf9651bfc81950534c53)): ?>
<?php $attributes = $__attributesOriginald54ce5ecfb77cf9651bfc81950534c53; ?>
<?php unset($__attributesOriginald54ce5ecfb77cf9651bfc81950534c53); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald54ce5ecfb77cf9651bfc81950534c53)): ?>
<?php $component = $__componentOriginald54ce5ecfb77cf9651bfc81950534c53; ?>
<?php unset($__componentOriginald54ce5ecfb77cf9651bfc81950534c53); ?>
<?php endif; ?>
    </div>

    <form method="POST" action="<?php echo e(route('productos.update', $producto)); ?>" class="flex max-w-[760px] flex-col gap-5 rounded-[14px] border border-slate-200 bg-white p-7">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <?php echo $__env->make('productos._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="<?php echo e(route('productos.index')); ?>" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Guardar cambios</button>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/productos/edit.blade.php ENDPATH**/ ?>
<?php $__env->startSection('titulo', 'Editar bodega'); ?>
<?php $__env->startSection('migas'); ?>
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('bodegas.index')); ?>" class="text-marca-600 hover:text-marca-900">Bodegas</a>
    <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('bodegas.show', $bodega)); ?>" class="text-marca-600 hover:text-marca-900"><?php echo e($bodega->codigo); ?></a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Editar</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <h1 class="text-2xl font-semibold tracking-tight">Editar <?php echo e($bodega->nombre); ?></h1>

    <form method="POST" action="<?php echo e(route('bodegas.update', $bodega)); ?>" class="flex max-w-[760px] flex-col gap-5 rounded-[14px] border border-slate-200 bg-white p-7">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <?php echo $__env->make('bodegas._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="<?php echo e(route('bodegas.show', $bodega)); ?>" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Guardar cambios</button>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/bodegas/edit.blade.php ENDPATH**/ ?>
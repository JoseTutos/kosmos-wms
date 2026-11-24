<?php $__env->startSection('titulo', 'Nuevo BL'); ?>

<?php $__env->startSection('migas'); ?>
    Entrada <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('bls.index')); ?>" class="hover:underline">BL</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Nuevo</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Nuevo BL</h1>
        <p class="mt-1 text-sm text-slate-500">Queda PROGRAMADO hasta que el operario inicie la recepción en la app.</p>
    </div>

    <form method="POST" action="<?php echo e(route('bls.store')); ?>" enctype="multipart/form-data"
          class="flex flex-col gap-6 rounded-[14px] border border-slate-200 bg-white p-6">
        <?php echo csrf_field(); ?>
        <?php echo $__env->make('bls._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="<?php echo e(route('bls.index')); ?>" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm font-medium hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700">Registrar BL</button>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/bls/create.blade.php ENDPATH**/ ?>
<?php $__env->startSection('titulo', 'Nuevo usuario'); ?>
<?php $__env->startSection('migas'); ?>
    Administración <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('usuarios.index')); ?>" class="text-marca-600 hover:text-marca-900">Usuarios</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Nuevo</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <h1 class="text-2xl font-semibold tracking-tight">Nuevo usuario</h1>

    <form method="POST" action="<?php echo e(route('usuarios.store')); ?>"
          class="flex max-w-[760px] flex-col gap-6 rounded-[14px] border border-slate-200 bg-white p-7">
        <?php echo csrf_field(); ?>
        <?php echo $__env->make('usuarios._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="flex items-start gap-2.5 rounded-[10px] bg-marca-50 px-3.5 py-3 text-sm text-slate-700">
            <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'info','class' => 'mt-px size-[18px] text-marca-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'info','class' => 'mt-px size-[18px] text-marca-600']); ?>
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
            <span>Al guardar, el sistema genera una contraseña temporal y se la muestra una sola vez. El usuario deberá cambiarla en su primer ingreso.</span>
        </div>

        <div class="flex flex-wrap justify-end gap-2.5 border-t border-slate-200 pt-5">
            <a href="<?php echo e(route('usuarios.index')); ?>" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-[18px] text-sm hover:bg-slate-50">Cancelar</a>
            <button type="submit" class="h-11 rounded-[10px] bg-marca-600 px-[18px] text-sm font-semibold text-white hover:bg-marca-700">Crear usuario</button>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/usuarios/create.blade.php ENDPATH**/ ?>
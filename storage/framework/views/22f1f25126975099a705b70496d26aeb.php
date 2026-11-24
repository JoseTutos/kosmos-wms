<?php use \App\Support\Etiquetas; ?>
<?php use \App\Support\MenuPrincipal; ?>

<?php $__env->startSection('titulo', 'Inicio'); ?>
<?php $__env->startSection('migas', 'Inicio'); ?>

<?php $__env->startSection('contenido'); ?>
    <?php
        $yo        = auth()->user();
        $accion    = MenuPrincipal::accionPrincipal($yo);
        $secciones = MenuPrincipal::secciones($yo);
    ?>

    <section class="flex flex-wrap items-center justify-between gap-4 rounded-[14px] border border-slate-200 bg-white px-7 py-6">
        <div>
            <h1 class="text-[26px] font-semibold tracking-tight">Bienvenido, <?php echo e(\Illuminate\Support\Str::before($yo->nombre, ' ')); ?></h1>
            <p class="mt-1.5 text-[15px] text-slate-600">Panel del <?php echo e(Etiquetas::rol($yo->rol)); ?> · Operador Logístico Zona Franca</p>
        </div>
        <?php if($accion): ?>
            <a href="<?php echo e(route($accion['ruta'])); ?>"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
                <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'mas','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'mas','class' => 'size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> <?php echo e($accion['label']); ?>

            </a>
        <?php endif; ?>
    </section>

    <?php $__empty_1 = true; $__currentLoopData = $secciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seccion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <section class="flex flex-col gap-3" aria-labelledby="sec-<?php echo e($loop->index); ?>">
            <h2 id="sec-<?php echo e($loop->index); ?>" class="text-[15px] font-semibold text-slate-700"><?php echo e($seccion['titulo']); ?></h2>
            <div class="grid grid-cols-[repeat(auto-fill,minmax(230px,1fr))] gap-3.5">
                <?php $__currentLoopData = $seccion['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route($item['ruta'])); ?>"
                       class="flex items-start gap-3.5 rounded-[14px] border border-slate-200 bg-white p-[18px] transition hover:border-marca-600/40 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-marca-600">
                        <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['flex size-10 shrink-0 items-center justify-center rounded-[10px]',
                                      'bg-yellow-50 text-yellow-900' => $item['icono'] === 'cuarentena',
                                      'bg-orange-50 text-acento-700' => $item['icono'] === 'salida',
                                      'bg-marca-100/60 text-marca-600' => ! in_array($item['icono'], ['cuarentena', 'salida'])]); ?>">
                            <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => $item['icono']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['icono'])]); ?>
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
                        </span>
                        <span>
                            <span class="block text-[15px] font-semibold text-slate-800"><?php echo e($item['label']); ?></span>
                            <span class="mt-0.5 block text-[13px] leading-snug text-slate-500"><?php echo e($item['desc']); ?></span>
                        </span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </section>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p class="rounded-[14px] border border-slate-200 bg-white p-6 text-sm text-slate-500">
            Aún no hay módulos habilitados para su rol.
        </p>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/dashboard.blade.php ENDPATH**/ ?>
<?php use \App\Models\Legalizacion; ?>

<?php $__env->startSection('titulo', 'Legalizaciones'); ?>

<?php $__env->startSection('migas'); ?>
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Legalizaciones</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Legalizaciones</h1>
            <p class="mt-1 text-sm text-slate-500">Mercancía que pasó de cuarentena a legalizada. Una legalización no se edita ni se borra: un error se corrige con un ajuste.</p>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', Legalizacion::class)): ?>
            <a href="<?php echo e(route('legalizaciones.create')); ?>"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
                <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'check','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'check','class' => 'size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Nueva legalización
            </a>
        <?php endif; ?>
    </div>

    <form method="GET" action="<?php echo e(route('legalizaciones.index')); ?>" class="flex flex-wrap items-end gap-3 rounded-[14px] border border-slate-200 bg-white p-5">
        <div class="flex min-w-64 flex-1 flex-col gap-1.5">
            <label for="q" class="text-sm font-medium">Buscar</label>
            <input id="q" name="q" type="search" value="<?php echo e(request('q')); ?>" maxlength="60" placeholder="LEG, número de autorización o BL"
                   class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        <div class="flex min-w-56 flex-col gap-1.5">
            <label for="cliente_id" class="text-sm font-medium">Cliente</label>
            <select id="cliente_id" name="cliente_id" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                <option value="">Todos</option>
                <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cliente): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($cliente->id); ?>" <?php if((string) request('cliente_id') === (string) $cliente->id): echo 'selected'; endif; ?>><?php echo e($cliente->codigo_corto); ?> · <?php echo e($cliente->razon_social); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <button type="submit" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
        <?php if(request()->hasAny(['q', 'cliente_id'])): ?>
            <a href="<?php echo e(route('legalizaciones.index')); ?>" class="inline-flex h-[46px] items-center rounded-[10px] border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Legalización</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Fecha</th>
                        <th scope="col" class="px-4 py-3 font-semibold">BL · Cliente</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Autorización</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Líneas</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $legalizaciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $legalizacion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="<?php echo e(route('legalizaciones.show', $legalizacion)); ?>" class="font-mono text-[13px] font-semibold text-marca-600 hover:underline"><?php echo e($legalizacion->consecutivo); ?></a>
                        </td>
                        <td class="px-4 py-3 tabular-nums"><?php echo e($legalizacion->created_at?->format('d/m/Y H:i')); ?></td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-[13px]"><?php echo e($legalizacion->bl->numero_bl); ?></span>
                            <span class="text-xs text-slate-500">· <?php echo e($legalizacion->bl->cliente->codigo_corto); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="block"><?php echo e($legalizacion->tipo_documento->etiqueta()); ?></span>
                            <span class="font-mono text-xs text-slate-500"><?php echo e($legalizacion->numero_autorizacion); ?></span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums"><?php echo e($legalizacion->detalles_count); ?></td>
                        <td class="px-4 py-3"><?php echo e($legalizacion->usuario->nombre); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No hay legalizaciones registradas con estos filtros.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php echo e($legalizaciones->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/legalizaciones/index.blade.php ENDPATH**/ ?>
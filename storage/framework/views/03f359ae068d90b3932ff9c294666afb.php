<?php use \App\Models\Ajuste; ?>

<?php $__env->startSection('titulo', 'Ajustes de inventario'); ?>

<?php $__env->startSection('migas'); ?>
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Ajustes</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Ajustes de inventario</h1>
            <p class="mt-1 text-sm text-slate-500">Correcciones de cantidades con motivo y descripción. Son irreversibles: un ajuste equivocado se corrige con otro ajuste.</p>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', Ajuste::class)): ?>
            <a href="<?php echo e(route('ajustes.create')); ?>"
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
<?php endif; ?> Nuevo ajuste
            </a>
        <?php endif; ?>
    </div>

    <form method="GET" action="<?php echo e(route('ajustes.index')); ?>" class="flex flex-wrap items-end gap-3 rounded-[14px] border border-slate-200 bg-white p-5">
        <div class="flex min-w-56 flex-1 flex-col gap-1.5">
            <label for="q" class="text-sm font-medium">Buscar</label>
            <input id="q" name="q" type="search" value="<?php echo e(request('q')); ?>" maxlength="50" placeholder="AJ, bulto, BL o SKU"
                   class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        <?php $__currentLoopData = ['motivo' => [$motivos, 'Motivo'], 'tipo' => [$tipos, 'Tipo'], 'estado' => [$estados, 'Estado']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $campo => [$opciones, $rotulo]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex flex-col gap-1.5">
                <label for="<?php echo e($campo); ?>" class="text-sm font-medium"><?php echo e($rotulo); ?></label>
                <select id="<?php echo e($campo); ?>" name="<?php echo e($campo); ?>" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $opciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $valor => $etiqueta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($valor); ?>" <?php if(request($campo) === $valor): echo 'selected'; endif; ?>><?php echo e($etiqueta); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div class="flex flex-col gap-1.5">
            <label for="desde" class="text-sm font-medium">Desde</label>
            <input id="desde" name="desde" type="date" value="<?php echo e(request('desde')); ?>" class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="hasta" class="text-sm font-medium">Hasta</label>
            <input id="hasta" name="hasta" type="date" value="<?php echo e(request('hasta')); ?>" class="h-[46px] rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        <button type="submit" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
        <?php if(request()->hasAny(['q', 'motivo', 'tipo', 'estado', 'desde', 'hasta'])): ?>
            <a href="<?php echo e(route('ajustes.index')); ?>" class="inline-flex h-[46px] items-center rounded-[10px] border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Ajuste</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Fecha</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Bulto · Producto</th>
                        <th scope="col" class="px-4 py-3 font-semibold">BL</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Cantidad</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Motivo</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $ajustes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ajuste): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $positivo = $ajuste->tipo === \App\Enums\TipoAjuste::POSITIVO;
                    ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="<?php echo e(route('ajustes.show', $ajuste)); ?>" class="font-mono text-[13px] font-semibold text-marca-600 hover:underline"><?php echo e($ajuste->consecutivo); ?></a>
                        </td>
                        <td class="px-4 py-3 tabular-nums"><?php echo e($ajuste->created_at?->format('d/m/Y H:i')); ?></td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-[13px]"><?php echo e($ajuste->bultoDetalle->bulto->codigo); ?></span>
                            <span class="text-xs text-slate-500">· <?php echo e($ajuste->bultoDetalle->producto->sku); ?></span>
                        </td>
                        <td class="px-4 py-3 font-mono text-[13px]"><?php echo e($ajuste->bultoDetalle->bl->numero_bl); ?></td>
                        <td class="px-4 py-3"><?php echo e($ajuste->estado_afectado->etiqueta()); ?></td>
                        <td class="<?php echo \Illuminate\Support\Arr::toCssClasses(['px-4 py-3 text-right font-semibold tabular-nums', 'text-green-700' => $positivo, 'text-red-700' => ! $positivo]); ?>">
                            <?php echo e($positivo ? '+' : '−'); ?><?php echo e($ajuste->cantidad); ?> <span class="text-xs font-normal text-slate-500"><?php echo e($ajuste->bultoDetalle->producto->unidad_medida->value); ?></span>
                        </td>
                        <td class="px-4 py-3"><?php echo e($ajuste->motivo->etiqueta()); ?></td>
                        <td class="px-4 py-3"><?php echo e($ajuste->usuario->nombre); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No hay ajustes registrados con estos filtros.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php echo e($ajustes->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/ajustes/index.blade.php ENDPATH**/ ?>
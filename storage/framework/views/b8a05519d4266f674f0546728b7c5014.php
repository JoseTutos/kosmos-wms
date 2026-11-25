<?php use \Illuminate\Support\Js; ?>

<?php $__env->startSection('titulo', $legalizacion->consecutivo); ?>


<?php $__env->startSection('migas'); ?>
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <?php if($historico ?? false): ?>
        <a href="<?php echo e(route('historicos.legalizaciones.index')); ?>" class="hover:underline">Históricos</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <?php else: ?>
        <a href="<?php echo e(route('legalizaciones.index')); ?>" class="hover:underline">Legalizaciones</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <?php endif; ?>
    <span class="font-medium text-slate-800"><?php echo e($legalizacion->consecutivo); ?></span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <?php
        $seriales = $legalizacion->detalles->sum(fn ($d) => $d->seriales->count());
        $prefijo = ($historico ?? false) ? 'historicos.legalizaciones' : 'legalizaciones';
    ?>

    
    <?php if(session('limpiar_carrito')): ?>
        <div hidden x-data x-init="try { localStorage.removeItem(<?php echo e(Js::from('kosmos.legalizacion.bl.'.session('limpiar_carrito'))); ?>) } catch (e) {}"></div>
    <?php endif; ?>

    <?php if(session('aviso')): ?>
        <div role="status" class="flex items-start gap-2.5 rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
            <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'alerta','class' => 'mt-0.5 size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'alerta','class' => 'mt-0.5 size-[18px]']); ?>
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
            <span><?php echo e(session('aviso')); ?></span>
        </div>
    <?php endif; ?>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-mono text-2xl font-semibold tracking-tight"><?php echo e($legalizacion->consecutivo); ?></h1>
            <p class="mt-1 text-sm text-slate-500">
                BL <span class="font-mono"><?php echo e($legalizacion->bl->numero_bl); ?></span>
                · <?php echo e($legalizacion->bl->cliente->codigo_corto); ?> · <?php echo e($legalizacion->bl->cliente->razon_social); ?>

            </p>
        </div>
        <a href="<?php echo e(route($prefijo.'.soporte', $legalizacion)); ?>"
           class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
            <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'abajo','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'abajo','class' => 'size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Descargar soporte
        </a>
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Documento</p>
            <p class="mt-1 font-semibold"><?php echo e($legalizacion->tipo_documento->etiqueta()); ?></p>
            <p class="font-mono text-sm"><?php echo e($legalizacion->numero_autorizacion); ?></p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Fecha de autorización</p>
            <p class="mt-1 text-xl font-semibold tabular-nums"><?php echo e($legalizacion->fecha_autorizacion->format('d/m/Y')); ?></p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Registró</p>
            <p class="mt-1 font-semibold"><?php echo e($legalizacion->usuario->nombre); ?></p>
            <p class="text-sm tabular-nums text-slate-500"><?php echo e($legalizacion->created_at?->format('d/m/Y H:i')); ?></p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Líneas · seriales</p>
            <p class="mt-1 text-xl font-semibold tabular-nums"><?php echo e($legalizacion->detalles->count()); ?> <span class="text-base font-normal text-slate-500">· <?php echo e($seriales); ?></span></p>
        </div>
    </div>

    <?php if($otrosUsos->isNotEmpty()): ?>
        <div class="flex items-start gap-2.5 rounded-[14px] border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900">
            <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'info','class' => 'mt-px size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'info','class' => 'mt-px size-[18px]']); ?>
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
            <span>
                Este número de autorización también aparece en:
                <?php $__currentLoopData = $otrosUsos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $uso): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route($prefijo.'.show', $uso)); ?>" class="font-mono font-semibold hover:underline"><?php echo e($uso->consecutivo); ?></a><?php echo e($loop->last ? '.' : ','); ?>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-[15px] font-semibold">Detalle legalizado</h2>
            <p class="text-xs text-slate-500">Registro de solo lectura. Para corregir un error registre un ajuste de inventario.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Bulto</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Pallet · Bodega</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Producto</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Cantidad</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Seriales</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $legalizacion->detalles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detalle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $linea = $detalle->bultoDetalle;
                    ?>
                    <tr class="border-t border-slate-100 align-top">
                        <td class="px-4 py-3 font-mono text-[13px] font-semibold"><?php echo e($linea->bulto->codigo); ?></td>
                        <td class="px-4 py-3 font-mono text-[13px]"><?php echo e($linea->bulto->pallet?->codigo); ?> · <?php echo e($linea->bulto->pallet?->bodega?->codigo); ?></td>
                        <td class="px-4 py-3" title="<?php echo e($linea->producto->descripcion); ?>"><?php echo e($linea->producto->sku); ?> <span class="text-xs text-slate-500"><?php echo e($linea->producto->unidad_medida->value); ?></span></td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><?php echo e($detalle->cantidad); ?></td>
                        <td class="px-4 py-3">
                            <?php if($detalle->seriales->isEmpty()): ?>
                                <span class="text-slate-400">—</span>
                            <?php else: ?>
                                <details>
                                    <summary class="cursor-pointer text-marca-600"><?php echo e($detalle->seriales->count()); ?> seriales</summary>
                                    <ul class="mt-2 grid grid-cols-2 gap-x-4 gap-y-0.5 font-mono text-xs">
                                        <?php $__currentLoopData = $detalle->seriales; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fila): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li><?php echo e($fila->serial->codigo); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Esta legalización no tiene líneas.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/legalizaciones/show.blade.php ENDPATH**/ ?>
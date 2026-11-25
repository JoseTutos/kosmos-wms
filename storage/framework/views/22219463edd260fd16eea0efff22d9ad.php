<?php use \App\Enums\TipoAjuste; ?>

<?php $__env->startSection('titulo', $ajuste->consecutivo); ?>

<?php $__env->startSection('migas'); ?>
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('ajustes.index')); ?>" class="hover:underline">Ajustes</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800"><?php echo e($ajuste->consecutivo); ?></span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <?php
        $linea = $ajuste->bultoDetalle;
        $positivo = $ajuste->tipo === TipoAjuste::POSITIVO;
        $unidad = $linea->producto->unidad_medida->value;
    ?>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-mono text-2xl font-semibold tracking-tight"><?php echo e($ajuste->consecutivo); ?></h1>
            <p class="mt-1 text-sm text-slate-500">
                BL <span class="font-mono"><?php echo e($linea->bl->numero_bl); ?></span> · <?php echo e($linea->bl->cliente->codigo_corto); ?> · <?php echo e($linea->bl->cliente->razon_social); ?>

            </p>
        </div>
        <?php if($ajuste->soporte_path): ?>
            <a href="<?php echo e(route('ajustes.soporte', $ajuste)); ?>"
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
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Ajuste</p>
            <p class="<?php echo \Illuminate\Support\Arr::toCssClasses(['mt-1 text-2xl font-semibold tabular-nums', 'text-green-700' => $positivo, 'text-red-700' => ! $positivo]); ?>">
                <?php echo e($positivo ? '+' : '−'); ?><?php echo e($ajuste->cantidad); ?> <span class="text-base font-normal text-slate-500"><?php echo e($unidad); ?></span>
            </p>
            <p class="text-sm text-slate-500"><?php echo e($ajuste->tipo->etiqueta()); ?> · <?php echo e($ajuste->estado_afectado->etiqueta()); ?></p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Bulto · Producto</p>
            <p class="mt-1 font-mono font-semibold"><?php echo e($linea->bulto->codigo); ?></p>
            <p class="text-sm" title="<?php echo e($linea->producto->descripcion); ?>"><?php echo e($linea->producto->sku); ?> · <?php echo e($linea->bulto->pallet?->codigo); ?> · <?php echo e($linea->bulto->pallet?->bodega?->codigo); ?></p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Motivo</p>
            <p class="mt-1 font-semibold"><?php echo e($ajuste->motivo->etiqueta()); ?></p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Registró</p>
            <p class="mt-1 font-semibold"><?php echo e($ajuste->usuario->nombre); ?></p>
            <p class="text-sm tabular-nums text-slate-500"><?php echo e($ajuste->created_at?->format('d/m/Y H:i')); ?></p>
        </div>
    </div>

    <div class="flex flex-col gap-4 rounded-[14px] border border-slate-200 bg-white p-5">
        <div>
            <h2 class="text-[15px] font-semibold">Descripción</h2>
            <p class="mt-1 whitespace-pre-line text-sm"><?php echo e($ajuste->descripcion); ?></p>
        </div>

        <?php if($ajuste->movimientoCorregido): ?>
            <div>
                <h2 class="text-[15px] font-semibold">Corrige el movimiento</h2>
                <p class="mt-1 font-mono text-sm">
                    #<?php echo e($ajuste->movimientoCorregido->id); ?> · <?php echo e($ajuste->movimientoCorregido->tipo->value); ?>

                    · <?php echo e($ajuste->movimientoCorregido->cantidad); ?> <?php echo e($ajuste->movimientoCorregido->estado_legal?->etiqueta()); ?>

                    · <?php echo e($ajuste->movimientoCorregido->documento_ref ?? 'sin referencia'); ?>

                    · <?php echo e($ajuste->movimientoCorregido->created_at?->format('d/m/Y H:i')); ?>

                </p>
            </div>
        <?php endif; ?>

        <?php if($movimiento): ?>
            <div>
                <h2 class="text-[15px] font-semibold">Movimiento generado</h2>
                <p class="mt-1 font-mono text-sm">#<?php echo e($movimiento->id); ?> · AJUSTE · <?php echo e($movimiento->cantidad); ?> <?php echo e($movimiento->estado_legal?->etiqueta()); ?></p>
            </div>
        <?php endif; ?>

        <p class="text-xs text-slate-500">Registro de solo lectura: un ajuste no se edita ni se borra.</p>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/ajustes/show.blade.php ENDPATH**/ ?>
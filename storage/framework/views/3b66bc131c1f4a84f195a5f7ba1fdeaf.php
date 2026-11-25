<?php use \App\Models\Legalizacion; ?>
<?php use \App\Services\Control\EstadoLegalService; ?>
<?php use \App\Support\Milesimas; ?>

<?php $__env->startSection('titulo', 'Estado legal · '.$bl->numero_bl); ?>

<?php $__env->startSection('migas'); ?>
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('estado-legal.index')); ?>" class="hover:underline">Estado legal</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800"><?php echo e($bl->numero_bl); ?></span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <?php
        $enCuarentena = $totales && Milesimas::de((string) $totales->total_cuarentena) > 0;
        $indicador = $totales ? EstadoLegalService::indicador($totales) : null;
    ?>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-mono text-2xl font-semibold tracking-tight"><?php echo e($bl->numero_bl); ?></h1>
            <p class="mt-1 text-sm text-slate-500">
                <?php echo e($bl->cliente->codigo_corto); ?> · <?php echo e($bl->cliente->razon_social); ?> · ingreso <?php echo e($bl->fecha_ingreso->format('d/m/Y')); ?>

                <?php if($totales): ?> · <?php echo e((int) $totales->dias); ?> días <?php endif; ?>
                · <?php echo e($bl->estado->value); ?>

            </p>
        </div>
        <?php if($legalizable && $enCuarentena && Route::has('legalizaciones.create')): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', Legalizacion::class)): ?>
                <a href="<?php echo e(route('legalizaciones.create', ['bl' => $bl->id])); ?>"
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
<?php endif; ?> Legalizar este BL
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="grid max-w-[900px] grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">En cuarentena</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums"><?php echo e($totales->total_cuarentena ?? '0.000'); ?> <span class="text-base font-normal text-slate-500">/ <?php echo e($totales->total_recibida ?? '0.000'); ?></span></p>
            <p class="text-xs text-slate-500">
                <?php if(! $enCuarentena): ?>
                    Todo legalizado: el BL ya no aparece en el estado legal activo.
                <?php elseif($indicador === EstadoLegalService::INDICADOR_AFORO): ?>
                    Hay un aforo en proceso.
                <?php elseif($indicador === EstadoLegalService::INDICADOR_TOTAL): ?>
                    Nada legalizado todavía.
                <?php else: ?>
                    Legalización parcial.
                <?php endif; ?>
            </p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Legalizado</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums"><?php echo e($totales->total_legalizada ?? '0.000'); ?></p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Preinspección · discrepancia</p>
            <p class="mt-1 font-semibold"><?php echo e($bl->fecha_preinspeccion?->format('d/m/Y') ?? 'Sin preinspección'); ?></p>
            <p class="text-sm tabular-nums text-slate-500"><?php echo e((int) $bl->discrepancia_bultos > 0 ? '+' : ''); ?><?php echo e((int) $bl->discrepancia_bultos); ?> bultos</p>
        </div>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-[15px] font-semibold">Detalle por bulto y producto</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Bulto</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Pallet · Lugar</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Producto</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Recibida</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Legalizada</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">En cuarentena</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $lineas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $linea): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $pendiente = Milesimas::de($linea->en_cuarentena) > 0;
                        $pallet = $linea->bulto->pallet;
                        $lugar = $pallet?->bodega?->codigo.($pallet?->ubicacion ? ' / '.$pallet->ubicacion->codigo : '');
                    ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <span class="font-mono text-[13px] font-semibold"><?php echo e($linea->bulto->codigo); ?></span>
                            <span class="block text-xs text-slate-500"><?php echo e($linea->bulto->estado->value); ?></span>
                        </td>
                        <td class="px-4 py-3 font-mono text-[13px]"><?php echo e($pallet?->codigo ?? '—'); ?> · <?php echo e($lugar ?: 'sin ubicar'); ?></td>
                        <td class="px-4 py-3" title="<?php echo e($linea->producto->descripcion); ?>"><?php echo e($linea->producto->sku); ?> <span class="text-xs text-slate-500"><?php echo e($linea->producto->unidad_medida->value); ?></span></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?php echo e($linea->cantidad_recibida); ?></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?php echo e($linea->cantidad_legalizada); ?></td>
                        <td class="<?php echo \Illuminate\Support\Arr::toCssClasses(['px-4 py-3 text-right font-semibold tabular-nums', 'text-red-700' => $pendiente, 'text-slate-400' => ! $pendiente]); ?>"><?php echo e($linea->en_cuarentena); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">El BL no tiene bultos.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/estado-legal/show.blade.php ENDPATH**/ ?>
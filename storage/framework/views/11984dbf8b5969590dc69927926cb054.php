<?php use \App\Support\Milesimas; ?>
<?php use \Illuminate\Support\Js; ?>

<?php $__env->startSection('titulo', 'Aforo AFO-'.$aforo->id); ?>

<?php $__env->startSection('migas'); ?>
    Entrada <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('aforos.index')); ?>" class="hover:underline">Aforos</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">AFO-<?php echo e($aforo->id); ?></span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <?php
        $total = $aforo->detalles->count();
        $contadas = $aforo->detalles->whereNotNull('cantidad_contada')->count();
        $conDiferencia = $aforo->detalles->filter(fn ($d) => $d->cantidad_contada !== null && Milesimas::de($d->diferencia) !== 0)->count();
        $pct = $total > 0 ? (int) round($contadas * 100 / $total) : 0;
    ?>

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-2xl font-semibold tracking-tight">AFO-<?php echo e($aforo->id); ?></h1>
                <?php if (isset($component)) { $__componentOriginal6df6258be5b33179d6917fa99563dc98 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6df6258be5b33179d6917fa99563dc98 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.estado-aforo','data' => ['estado' => $aforo->estado]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('estado-aforo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['estado' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($aforo->estado)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6df6258be5b33179d6917fa99563dc98)): ?>
<?php $attributes = $__attributesOriginal6df6258be5b33179d6917fa99563dc98; ?>
<?php unset($__attributesOriginal6df6258be5b33179d6917fa99563dc98); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6df6258be5b33179d6917fa99563dc98)): ?>
<?php $component = $__componentOriginal6df6258be5b33179d6917fa99563dc98; ?>
<?php unset($__componentOriginal6df6258be5b33179d6917fa99563dc98); ?>
<?php endif; ?>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                BL
                <?php if(Route::has('bls.show')): ?>
                    <a href="<?php echo e(route('bls.show', $aforo->bl)); ?>" class="font-mono text-marca-600 hover:underline"><?php echo e($aforo->bl->numero_bl); ?></a>
                <?php else: ?>
                    <span class="font-mono"><?php echo e($aforo->bl->numero_bl); ?></span>
                <?php endif; ?>
                · <?php echo e($aforo->bl->cliente->codigo_corto); ?> · <?php echo e($aforo->bl->cliente->razon_social); ?>

            </p>
        </div>

        <div class="flex flex-wrap gap-2.5">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reporte', $aforo)): ?>
                <a href="<?php echo e(route('aforos.reporte', [$aforo, 'pdf'])); ?>"
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
<?php endif; ?> Reporte PDF
                </a>
                <a href="<?php echo e(route('aforos.reporte', [$aforo, 'xlsx'])); ?>"
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
<?php endif; ?> Reporte Excel
                </a>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('cancelar', $aforo)): ?>
                <?php
                    $cancelar = [
                        'url' => route('aforos.cancelar', $aforo), 'metodo' => 'PATCH', 'peligro' => true,
                        'titulo' => '¿Cancelar el aforo AFO-'.$aforo->id.'?',
                        'detalle' => 'BL '.$aforo->bl->numero_bl.' · '.$contadas.' de '.$total.' líneas contadas',
                        'puntos' => ['Los conteos no se aplican a las cantidades.', 'Se borran los seriales generados en este aforo.', 'Los bultos vuelven a UBICADO. Todo queda en la bitácora.'],
                        'boton' => 'Cancelar aforo',
                    ];
                ?>
                <button type="button" @click="$dispatch('confirmar', <?php echo e(Js::from($cancelar)); ?>)"
                        class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-red-300 bg-white px-4 text-sm font-medium text-red-700 hover:bg-red-50">
                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'cerrar','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'cerrar','class' => 'size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Cancelar aforo
                </button>
            <?php endif; ?>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('cerrar', $aforo)): ?>
                <?php
                    $cerrar = [
                        'url' => route('aforos.cerrar', $aforo), 'metodo' => 'PATCH',
                        'titulo' => '¿Cerrar el aforo AFO-'.$aforo->id.'?',
                        'detalle' => $conDiferencia.' de '.$total.' líneas con diferencia',
                        'puntos' => ['Cada conteo reemplaza la cantidad recibida y corrige la cuarentena (movimiento AFORO).', 'Se genera el reporte de diferencias en PDF y Excel.', 'No se puede deshacer: después solo se corrige con un ajuste.'],
                        'boton' => 'Cerrar aforo',
                    ];
                ?>
                <button type="button" @click="$dispatch('confirmar', <?php echo e(Js::from($cerrar)); ?>)" <?php if($contadas < $total): echo 'disabled'; endif; ?>
                        title="<?php echo e($contadas < $total ? 'Faltan líneas por contar' : 'Aplicar los conteos'); ?>"
                        class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700 disabled:opacity-50">
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
<?php endif; ?> Cerrar aforo
                </button>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="grid max-w-[900px] grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Líneas contadas</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums"><?php echo e($contadas); ?> <span class="text-base font-normal text-slate-500">/ <?php echo e($total); ?></span></p>
            <div class="mt-2 h-2 rounded-full bg-slate-100" role="progressbar" aria-valuenow="<?php echo e($pct); ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="h-2 rounded-full bg-marca-600" style="width: <?php echo e($pct); ?>%"></div>
            </div>
        </div>
        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['rounded-[14px] border bg-white p-5', 'border-acento-300' => $conDiferencia > 0, 'border-slate-200' => $conDiferencia === 0]); ?>">
            <p class="text-[13px] text-slate-500">Con diferencia</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums"><?php echo e($conDiferencia); ?></p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Seriales generados</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums"><?php echo e($aforo->detalles->sum(fn ($d) => $d->bultoDetalle->seriales_count)); ?></p>
        </div>
    </div>

    <?php if($aforo->estaEnProceso()): ?>
        <div class="flex items-start gap-2.5 rounded-[14px] border border-marca-100 bg-marca-50 p-4 text-sm text-slate-700">
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
            <span>Los operarios cuentan desde la app (Aforos → AFO-<?php echo e($aforo->id); ?>). Recargue la página para ver el avance. Para cerrar, todas las líneas deben estar contadas.</span>
        </div>
    <?php endif; ?>

    
    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Bulto</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Pallet · Bodega</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Producto</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Manifiesto</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Contada</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Diferencia</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Seriales</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $aforo->detalles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detalle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $linea = $detalle->bultoDetalle;
                        $dif = $detalle->cantidad_contada === null ? null : Milesimas::de($detalle->diferencia);
                    ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-mono text-[13px] font-semibold"><?php echo e($linea->bulto->codigo); ?></td>
                        <td class="px-4 py-3 font-mono text-[13px]"><?php echo e($linea->bulto->pallet?->codigo); ?> · <?php echo e($linea->bulto->pallet?->bodega?->codigo); ?></td>
                        <td class="px-4 py-3" title="<?php echo e($linea->producto->descripcion); ?>"><?php echo e($linea->producto->sku); ?> <span class="text-xs text-slate-500"><?php echo e($linea->producto->unidad_medida->value); ?></span></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?php echo e($detalle->cantidad_manifiesto); ?></td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            <?php if($detalle->cantidad_contada === null): ?>
                                <span class="text-slate-400">Pendiente</span>
                            <?php else: ?>
                                <?php echo e($detalle->cantidad_contada); ?>

                            <?php endif; ?>
                        
                        
                            <?php
                                $faltante = $aforo->estaEnProceso() && $dif !== null
                                    ? Milesimas::de($linea->cantidad_legalizada) - Milesimas::de($detalle->cantidad_contada)
                                    : 0;
                            ?>
                            <?php if($faltante > 0): ?>
                                <span class="mt-1 block text-xs font-normal text-red-700">Menor que lo legalizado (<?php echo e($linea->cantidad_legalizada); ?>): bloquea el cierre.</span>
                                <?php if(Route::has('ajustes.create')): ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\Ajuste::class)): ?>
                                        <a href="<?php echo e(route('ajustes.create', [
                                                'bulto'       => $linea->bulto->codigo,
                                                'linea'       => $linea->id,
                                                'tipo'        => 'NEGATIVO',
                                                'estado'      => 'LEGALIZADA',
                                                'cantidad'    => Milesimas::texto($faltante),
                                                'descripcion' => 'AFO-'.$aforo->id.': se contaron '.$detalle->cantidad_contada.' y había '.$linea->cantidad_legalizada.' legalizadas (A2).',
                                                'aforo'       => $aforo->id,
                                            ])); ?>"
                                           class="mt-1 inline-flex h-8 items-center rounded-[8px] border border-red-300 bg-white px-2.5 text-xs font-semibold text-red-700 hover:bg-red-50">Registrar ajuste</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                            
                        </td>
                        <td class="<?php echo \Illuminate\Support\Arr::toCssClasses(['px-4 py-3 text-right font-semibold tabular-nums', 'text-red-700' => $dif !== null && $dif < 0, 'text-green-700' => $dif !== null && $dif > 0, 'text-slate-400' => $dif === null || $dif === 0]); ?>">
                            <?php echo e($dif === null ? '—' : ($dif > 0 ? '+' : '').$detalle->diferencia); ?>

                        </td>
                        <td class="px-4 py-3 text-right tabular-nums"><?php echo e($linea->seriales_count ?: '—'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Este aforo no tiene líneas.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/aforos/show.blade.php ENDPATH**/ ?>
<?php $__env->startSection('titulo', 'BL'); ?>

<?php $__env->startSection('migas'); ?>
    Entrada <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">BL</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">BL</h1>
            <p class="mt-1 text-sm text-slate-500">Documentos de transporte con su manifiesto. Solo se editan mientras están programados.</p>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Bl::class)): ?>
            <a href="<?php echo e(route('bls.create')); ?>"
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
<?php endif; ?> Nuevo BL
            </a>
        <?php endif; ?>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <form method="GET" action="<?php echo e(route('bls.index')); ?>" role="search" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4">
            <div class="flex min-w-56 flex-1 flex-col gap-1.5">
                <label for="q" class="text-xs font-medium text-slate-500">Buscar</label>
                <input id="q" name="q" type="search" value="<?php echo e(request('q')); ?>" maxlength="50" placeholder="Número de BL o factura"
                       class="h-10 rounded-[10px] border border-slate-300 px-3 text-sm outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="cliente_id" class="text-xs font-medium text-slate-500">Cliente</label>
                <select id="cliente_id" name="cliente_id" class="h-10 max-w-56 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($cl->id); ?>" <?php if((string) request('cliente_id') === (string) $cl->id): echo 'selected'; endif; ?>><?php echo e($cl->codigo_corto); ?> · <?php echo e($cl->razon_social); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="estado" class="text-xs font-medium text-slate-500">Estado</label>
                <select id="estado" name="estado" class="h-10 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $estados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $estado): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($estado->value); ?>" <?php if(request('estado') === $estado->value): echo 'selected'; endif; ?>><?php echo e(str_replace('_', ' ', ucfirst(strtolower($estado->value)))); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button type="submit" class="h-10 rounded-[10px] bg-slate-800 px-4 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
            <?php if(request()->hasAny(['q', 'cliente_id', 'estado'])): ?>
                <a href="<?php echo e(route('bls.index')); ?>" class="h-10 px-2 text-sm leading-10 text-marca-600 hover:underline">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Número BL</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Cliente</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Fecha de ingreso</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Factura</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Productos</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Bultos esperados</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $bls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <a href="<?php echo e(route('bls.show', $bl)); ?>" class="font-mono text-[13px] font-semibold text-marca-700 hover:underline"><?php echo e($bl->numero_bl); ?></a>
                        </td>
                        <td class="px-4 py-3" title="<?php echo e($bl->cliente->razon_social); ?>"><?php echo e($bl->cliente->codigo_corto); ?></td>
                        <td class="px-4 py-3 tabular-nums"><?php echo e($bl->fecha_ingreso->format('d/m/Y')); ?></td>
                        <td class="px-4 py-3"><?php echo e($bl->factura_comercial); ?></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?php echo e($bl->detalles_count); ?></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?php echo e((int) $bl->detalles_sum_bultos_esperados); ?></td>
                        <td class="px-4 py-3"><?php if (isset($component)) { $__componentOriginal13cfe373e4efdd5413e9190b757b2702 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal13cfe373e4efdd5413e9190b757b2702 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.estado-bl','data' => ['estado' => $bl->estado]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('estado-bl'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['estado' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bl->estado)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal13cfe373e4efdd5413e9190b757b2702)): ?>
<?php $attributes = $__attributesOriginal13cfe373e4efdd5413e9190b757b2702; ?>
<?php unset($__attributesOriginal13cfe373e4efdd5413e9190b757b2702); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal13cfe373e4efdd5413e9190b757b2702)): ?>
<?php $component = $__componentOriginal13cfe373e4efdd5413e9190b757b2702; ?>
<?php unset($__componentOriginal13cfe373e4efdd5413e9190b757b2702); ?>
<?php endif; ?></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="<?php echo e(route('bls.show', $bl)); ?>" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Ver</a>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $bl)): ?>
                                    <a href="<?php echo e(route('bls.edit', $bl)); ?>" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Editar</a>
                                <?php endif; ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $bl)): ?>
                                    <?php
                                        $accion = [
                                            'url' => route('bls.destroy', $bl), 'metodo' => 'DELETE', 'peligro' => true,
                                            'titulo' => '¿Eliminar el BL '.$bl->numero_bl.'?',
                                            'detalle' => $bl->cliente->codigo_corto.' · Factura '.$bl->factura_comercial,
                                            'puntos' => ['Se borra el BL con su manifiesto.', 'Solo es posible porque aún no tiene bultos ni pallets.', 'La eliminación queda en la bitácora.'],
                                            'boton' => 'Eliminar BL',
                                        ];
                                    ?>
                                    <button type="button" @click="$dispatch('confirmar', <?php echo e(Js::from($accion)); ?>)"
                                            class="h-9 rounded-lg border border-red-300 px-3 text-[13px] text-red-700 hover:bg-red-50">Eliminar</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">
                            <?php if(request()->hasAny(['q', 'cliente_id', 'estado'])): ?>
                                No hay BL con esos filtros.
                            <?php else: ?>
                                Registre el primer BL con su manifiesto de productos.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($bls->hasPages()): ?>
            <div class="border-t border-slate-200 px-4 py-3"><?php echo e($bls->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/bls/index.blade.php ENDPATH**/ ?>
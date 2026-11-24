<?php $__env->startSection('titulo', 'Aforos'); ?>

<?php $__env->startSection('migas'); ?>
    Entrada <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Aforos</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Aforos</h1>
            <p class="mt-1 text-sm text-slate-500">Inventario detallado de un BL recibido: el operario cuenta cada línea de bulto desde la app.</p>
        </div>
        <a href="<?php echo e(route('aforos.create')); ?>"
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
<?php endif; ?> Activar aforo
        </a>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <form method="GET" action="<?php echo e(route('aforos.index')); ?>" role="search" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4">
            <div class="flex min-w-60 flex-1 flex-col gap-1.5">
                <label for="q" class="text-xs font-medium text-slate-500">Buscar</label>
                <input id="q" name="q" type="search" value="<?php echo e(request('q')); ?>" maxlength="30" placeholder="Número de BL"
                       class="h-10 rounded-[10px] border border-slate-300 px-3 text-sm outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
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
            <?php if(request()->hasAny(['q', 'estado'])): ?>
                <a href="<?php echo e(route('aforos.index')); ?>" class="h-10 px-2 text-sm leading-10 text-marca-600 hover:underline">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Aforo</th>
                        <th scope="col" class="px-4 py-3 font-semibold">BL</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Cliente</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Líneas contadas</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Activado</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $aforos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $aforo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <a href="<?php echo e(route('aforos.show', $aforo)); ?>" class="font-mono text-[13px] font-semibold text-marca-700 hover:underline">AFO-<?php echo e($aforo->id); ?></a>
                        </td>
                        <td class="px-4 py-3 font-mono text-[13px]"><?php echo e($aforo->bl->numero_bl); ?></td>
                        <td class="px-4 py-3" title="<?php echo e($aforo->bl->cliente->razon_social); ?>"><?php echo e($aforo->bl->cliente->codigo_corto); ?></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?php echo e($aforo->contadas_count); ?> / <?php echo e($aforo->detalles_count); ?></td>
                        <td class="px-4 py-3 tabular-nums"><?php echo e($aforo->created_at?->timezone(config('kosmos.zona_horaria'))->format('d/m/Y H:i')); ?></td>
                        <td class="px-4 py-3"><?php if (isset($component)) { $__componentOriginal6df6258be5b33179d6917fa99563dc98 = $component; } ?>
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
<?php endif; ?></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <a href="<?php echo e(route('aforos.show', $aforo)); ?>" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Ver</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                            <?php if(request()->hasAny(['q', 'estado'])): ?>
                                No hay aforos con esos filtros.
                            <?php else: ?>
                                Aún no hay aforos. Actívelo sobre un BL recibido cuando el cliente o el operador lo soliciten.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($aforos->hasPages()): ?>
            <div class="border-t border-slate-200 px-4 py-3"><?php echo e($aforos->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/aforos/index.blade.php ENDPATH**/ ?>
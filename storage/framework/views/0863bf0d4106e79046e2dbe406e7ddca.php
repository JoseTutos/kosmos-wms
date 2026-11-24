<?php use \App\Support\Etiquetas; ?>
<?php use \Illuminate\Support\Js; ?>

<?php $__env->startSection('titulo', 'Clientes'); ?>
<?php $__env->startSection('migas'); ?>
    Maestros <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Clientes</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Clientes importadores</h1>
            <p class="mt-1 text-sm text-slate-500">NIT y código corto no se pueden cambiar. Los clientes se inactivan, no se eliminan.</p>
        </div>
        <a href="<?php echo e(route('clientes.create')); ?>"
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
<?php endif; ?> Nuevo cliente
        </a>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <form method="GET" action="<?php echo e(route('clientes.index')); ?>" role="search" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4">
            <div class="flex min-w-60 flex-1 flex-col gap-1.5">
                <label for="q" class="text-xs font-medium text-slate-500">Buscar</label>
                <input id="q" name="q" type="search" value="<?php echo e(request('q')); ?>" maxlength="100" placeholder="Razón social, NIT o código"
                       class="h-10 rounded-[10px] border border-slate-300 px-3 text-sm outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="estado" class="text-xs font-medium text-slate-500">Estado</label>
                <select id="estado" name="estado" class="h-10 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $estados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $estado): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($estado->value); ?>" <?php if(request('estado') === $estado->value): echo 'selected'; endif; ?>><?php echo e(Etiquetas::activo($estado)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button type="submit" class="h-10 rounded-[10px] bg-slate-800 px-4 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
            <?php if(request()->hasAny(['q', 'estado'])): ?>
                <a href="<?php echo e(route('clientes.index')); ?>" class="h-10 px-2 text-sm leading-10 text-marca-600 hover:underline">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Cliente</th>
                        <th scope="col" class="px-4 py-3 font-semibold">NIT</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Contacto</th>
                        <th scope="col" class="px-4 py-3 text-center font-semibold">Productos</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                   <?php
                        $activo = $c->estaActivo();
                    ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['rounded-md px-2 py-1 font-mono text-xs font-semibold',
                                              'bg-marca-100 text-marca-900' => $activo, 'bg-slate-100 text-slate-500' => ! $activo]); ?>"><?php echo e($c->codigo_corto); ?></span>
                                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['font-medium', 'text-slate-500' => ! $activo]); ?>"><?php echo e($c->razon_social); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-[13px]"><?php echo e($c->nit); ?></td>
                        <td class="px-4 py-3">
                            <span class="block"><?php echo e($c->contacto_nombre); ?></span>
                            <span class="block text-[13px] text-slate-500"><?php echo e($c->telefono); ?></span>
                        </td>
                        <td class="px-4 py-3 text-center"><?php echo e($c->productos_count); ?></td>
                        <td class="px-4 py-3">
                            <?php if (isset($component)) { $__componentOriginald54ce5ecfb77cf9651bfc81950534c53 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald54ce5ecfb77cf9651bfc81950534c53 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.estado','data' => ['activo' => $activo,'texto' => Etiquetas::activo($c->estado)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('estado'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['activo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activo),'texto' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(Etiquetas::activo($c->estado))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald54ce5ecfb77cf9651bfc81950534c53)): ?>
<?php $attributes = $__attributesOriginald54ce5ecfb77cf9651bfc81950534c53; ?>
<?php unset($__attributesOriginald54ce5ecfb77cf9651bfc81950534c53); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald54ce5ecfb77cf9651bfc81950534c53)): ?>
<?php $component = $__componentOriginald54ce5ecfb77cf9651bfc81950534c53; ?>
<?php unset($__componentOriginald54ce5ecfb77cf9651bfc81950534c53); ?>
<?php endif; ?>
                            <?php if(! $activo && $c->tiene_inventario): ?>
                                <span class="mt-1 block text-[11px] text-yellow-800">Con mercancía en bodega</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="<?php echo e(route('clientes.edit', $c)); ?>" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Editar</a>

                                <?php if($activo): ?>
                                    <?php
                                        $puntos = ['No podrá registrar BL ni productos nuevos.',
                                                   'Su historial (BL, legalizaciones, despachos) se conserva.',
                                                   'Podrá reactivarlo cuando quiera.'];
                                        if ($c->tiene_inventario) {
                                            $puntos[] = 'Tiene mercancía en bodega: seguirá legalizándose y despachándose con normalidad.';
                                        }
                                        $accion = [
                                            'url' => route('clientes.inactivar', $c), 'metodo' => 'PATCH', 'peligro' => true,
                                            'titulo' => '¿Inactivar a '.$c->razon_social.'?',
                                            'detalle' => $c->codigo_corto.' · NIT '.$c->nit,
                                            'puntos' => $puntos, 'boton' => 'Inactivar cliente',
                                        ];
                                    ?>
                                    <button type="button" @click="$dispatch('confirmar', <?php echo e(Js::from($accion)); ?>)"
                                            class="h-9 rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Inactivar</button>
                                <?php else: ?>
                                    <form method="POST" action="<?php echo e(route('clientes.activar', $c)); ?>">
                                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                        <button type="submit" class="h-9 rounded-lg border border-green-700 px-3 text-[13px] font-medium text-green-800 hover:bg-green-50">Reactivar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                            <?php if(request()->hasAny(['q', 'estado'])): ?>
                                No hay clientes con esos filtros.
                            <?php else: ?>
                                Registre su primer cliente importador para empezar.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($clientes->hasPages()): ?>
            <div class="border-t border-slate-200 px-4 py-3"><?php echo e($clientes->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/clientes/index.blade.php ENDPATH**/ ?>
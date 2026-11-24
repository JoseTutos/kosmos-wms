<?php use \App\Support\Etiquetas; ?>
<?php use \Illuminate\Support\Js; ?>

<?php $__env->startSection('titulo', 'Productos'); ?>
<?php $__env->startSection('migas'); ?>
    Maestros <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Productos</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Productos</h1>
            <p class="mt-1 text-sm text-slate-500">El SKU se genera solo con el código del cliente y no se puede cambiar.</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="<?php echo e(route('productos.importar')); ?>"
               class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
                Importar CSV
            </a>
            <a href="<?php echo e(route('productos.create')); ?>"
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
<?php endif; ?> Nuevo producto
            </a>
        </div>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <form method="GET" action="<?php echo e(route('productos.index')); ?>" role="search" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4">
            <div class="flex min-w-56 flex-1 flex-col gap-1.5">
                <label for="q" class="text-xs font-medium text-slate-500">Buscar</label>
                <input id="q" name="q" type="search" value="<?php echo e(request('q')); ?>" maxlength="100" placeholder="SKU o descripción"
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
                <label for="tipo" class="text-xs font-medium text-slate-500">Tipo</label>
                <select id="tipo" name="tipo" class="h-10 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $tipos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tipo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($tipo->value); ?>" <?php if(request('tipo') === $tipo->value): echo 'selected'; endif; ?>><?php echo e(Etiquetas::tipoAlmacenamiento($tipo)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
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
            <?php if(request()->hasAny(['q', 'cliente_id', 'tipo', 'estado'])): ?>
                <a href="<?php echo e(route('productos.index')); ?>" class="h-10 px-2 text-sm leading-10 text-marca-600 hover:underline">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">SKU</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Descripción</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Cliente</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Tipo</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Unidad</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Peso (kg)</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Stock mín.</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $productos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $activo = $p->estaActivo();
                    ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-mono text-[13px] font-semibold"><?php echo e($p->sku); ?></td>
                        <td class="<?php echo \Illuminate\Support\Arr::toCssClasses(['px-4 py-3', 'text-slate-500' => ! $activo]); ?>"><?php echo e($p->descripcion); ?></td>
                        <td class="px-4 py-3" title="<?php echo e($p->cliente->razon_social); ?>"><?php echo e($p->cliente->codigo_corto); ?></td>
                        <td class="px-4 py-3"><?php echo e(Etiquetas::tipoAlmacenamiento($p->tipo_almacenamiento)); ?></td>
                        <td class="px-4 py-3"><?php echo e($p->unidad_medida->value); ?></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?php echo e($p->peso_unitario_kg); ?></td>
                        <td class="px-4 py-3 text-right tabular-nums"><?php echo e($p->stock_minimo); ?></td>
                        <td class="px-4 py-3"><?php if (isset($component)) { $__componentOriginald54ce5ecfb77cf9651bfc81950534c53 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald54ce5ecfb77cf9651bfc81950534c53 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.estado','data' => ['activo' => $activo,'texto' => Etiquetas::activo($p->estado)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('estado'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['activo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activo),'texto' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(Etiquetas::activo($p->estado))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald54ce5ecfb77cf9651bfc81950534c53)): ?>
<?php $attributes = $__attributesOriginald54ce5ecfb77cf9651bfc81950534c53; ?>
<?php unset($__attributesOriginald54ce5ecfb77cf9651bfc81950534c53); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald54ce5ecfb77cf9651bfc81950534c53)): ?>
<?php $component = $__componentOriginald54ce5ecfb77cf9651bfc81950534c53; ?>
<?php unset($__componentOriginald54ce5ecfb77cf9651bfc81950534c53); ?>
<?php endif; ?></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="<?php echo e(route('productos.edit', $p)); ?>" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Editar</a>
                                <?php if($activo): ?>
                                    <?php
                                        $accion = [
                                            'url' => route('productos.inactivar', $p), 'metodo' => 'PATCH', 'peligro' => true,
                                            'titulo' => '¿Inactivar el producto '.$p->sku.'?',
                                            'detalle' => $p->descripcion,
                                            'puntos' => ['No se podrá incluir en BL nuevos.', 'Lo que ya está en bodega sigue su trámite normal.', 'Podrá reactivarlo cuando quiera.'],
                                            'boton' => 'Inactivar producto',
                                        ];
                                    ?>
                                    <button type="button" @click="$dispatch('confirmar', <?php echo e(Js::from($accion)); ?>)"
                                            class="h-9 rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Inactivar</button>
                                <?php else: ?>
                                    <form method="POST" action="<?php echo e(route('productos.activar', $p)); ?>">
                                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                        <button type="submit" class="h-9 rounded-lg border border-green-700 px-3 text-[13px] font-medium text-green-800 hover:bg-green-50">Reactivar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">
                            <?php if(request()->hasAny(['q', 'cliente_id', 'tipo', 'estado'])): ?>
                                No hay productos con esos filtros.
                            <?php else: ?>
                                Cree un producto o importe varios desde un archivo CSV.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($productos->hasPages()): ?>
            <div class="border-t border-slate-200 px-4 py-3"><?php echo e($productos->links()); ?></div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/productos/index.blade.php ENDPATH**/ ?>
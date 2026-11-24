<?php use \App\Support\Etiquetas; ?>
<?php use \Illuminate\Support\Js; ?>

<?php $__env->startSection('titulo', $bodega->codigo.' · Bodega'); ?>
<?php $__env->startSection('migas'); ?>
    Maestros <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('bodegas.index')); ?>" class="text-marca-600 hover:text-marca-900">Bodegas</a>
    <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800"><?php echo e($bodega->codigo); ?></span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <?php $pct = (float) $bodega->porcentaje_ocupacion; ?>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-md bg-marca-100 px-2.5 py-1 font-mono text-sm font-semibold text-marca-900"><?php echo e($bodega->codigo); ?></span>
            <h1 class="text-2xl font-semibold tracking-tight"><?php echo e($bodega->nombre); ?></h1>
            <?php if (isset($component)) { $__componentOriginald54ce5ecfb77cf9651bfc81950534c53 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald54ce5ecfb77cf9651bfc81950534c53 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.estado','data' => ['activo' => $bodega->aceptaMercancia(),'texto' => Etiquetas::activo($bodega->estado)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('estado'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['activo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bodega->aceptaMercancia()),'texto' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(Etiquetas::activo($bodega->estado))]); ?>
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
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php echo $__env->make('etiquetas._imprimir', ['tipo' => 'bodega', 'codigo' => $bodega->codigo, 'creado' => $bodega->created_at], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <a href="<?php echo e(route('bodegas.edit', $bodega)); ?>" class="inline-flex h-[42px] items-center gap-2 rounded-[10px] border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50">
                <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'editar','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'editar','class' => 'size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Editar bodega
            </a>
        </div>
    </div>

    
    <div class="grid max-w-[900px] grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Tamaño</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums"><?php echo e($bodega->tamano_m2); ?> <span class="text-base font-normal text-slate-500">m²</span></p>
        </div>
        <div class="rounded-[14px] border border-slate-200 bg-white p-5">
            <p class="text-[13px] text-slate-500">Ocupados</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums"><?php echo e($bodega->m2_ocupados); ?> <span class="text-base font-normal text-slate-500">m²</span></p>
        </div>
        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['rounded-[14px] border bg-white p-5', 'border-red-200' => $pct > 100, 'border-slate-200' => $pct <= 100]); ?>">
            <p class="text-[13px] text-slate-500">Ocupación</p>
            <div class="mt-2"><?php echo $__env->make('bodegas._ocupacion', ['bodega' => $bodega], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>
        </div>
    </div>

    
    <section class="max-w-[900px] rounded-[14px] border border-slate-200 bg-white" aria-labelledby="titulo-ubicaciones">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <h2 id="titulo-ubicaciones" class="text-[15px] font-semibold">Ubicaciones internas</h2>
            <?php if($bodega->maneja_ubicaciones): ?>
                <span class="text-[13px] text-slate-500"><?php echo e($bodega->ubicaciones->count()); ?> registradas</span>
            <?php endif; ?>
        </div>

        <?php if(! $bodega->maneja_ubicaciones): ?>
            <div class="flex items-start gap-2.5 p-5 text-sm text-slate-600">
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
                <span>
                    Esta bodega no maneja ubicaciones internas: al ubicar un pallet se escribe una referencia libre.
                    Si quiere codificar posiciones (A-01, A-02…), <a href="<?php echo e(route('bodegas.edit', $bodega)); ?>" class="font-medium text-marca-600 hover:text-marca-900">active las ubicaciones</a>.
                </span>
            </div>
        <?php else: ?>
            <form method="POST" action="<?php echo e(route('bodegas.ubicaciones.store', $bodega)); ?>" class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-5">
                <?php echo csrf_field(); ?>
                <div class="flex min-w-52 flex-col gap-1.5">
                    <label for="codigo" class="text-xs font-medium text-slate-500">Nueva ubicación</label>
                    <input id="codigo" name="codigo" type="text" value="<?php echo e(old('codigo')); ?>" required maxlength="20" placeholder="A-01" autocomplete="off"
                           <?php $__errorArgs = ['codigo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                           class="h-10 rounded-[10px] border border-slate-300 px-3 font-mono text-sm uppercase outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
                </div>
                <button type="submit" class="inline-flex h-10 items-center gap-1.5 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'mas','class' => 'size-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'mas','class' => 'size-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Agregar
                </button>
                <p class="basis-full text-xs text-slate-500">El código no se puede cambiar después: va en la etiqueta. Si se equivoca, inactívela y cree otra.</p>
                <?php $__errorArgs = ['codigo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="basis-full text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </form>

            <?php if($bodega->ubicaciones->isEmpty()): ?>
                <p class="p-5 text-sm text-slate-500">Aún no hay ubicaciones. Agregue la primera con el campo de arriba.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <th scope="col" class="px-5 py-3 font-semibold">Código</th>
                                <th scope="col" class="px-5 py-3 text-center font-semibold">Pallets ubicados</th>
                                <th scope="col" class="px-5 py-3 font-semibold">Estado</th>
                                <th scope="col" class="px-5 py-3 text-right font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $bodega->ubicaciones; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $activa = $u->estaActiva(); ?>
                                <tr class="border-t border-slate-100">
                                    <td class="<?php echo \Illuminate\Support\Arr::toCssClasses(['px-5 py-3 font-mono font-semibold', 'text-slate-500' => ! $activa]); ?>"><?php echo e($u->codigo); ?></td>
                                    <td class="px-5 py-3 text-center tabular-nums"><?php echo e($u->pallets_ubicados); ?></td>
                                    <td class="px-5 py-3"><?php if (isset($component)) { $__componentOriginald54ce5ecfb77cf9651bfc81950534c53 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald54ce5ecfb77cf9651bfc81950534c53 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.estado','data' => ['activo' => $activa,'texto' => Etiquetas::activo($u->estado)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('estado'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['activo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activa),'texto' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(Etiquetas::activo($u->estado))]); ?>
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
                                    <td class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <?php echo $__env->make('etiquetas._imprimir', ['tipo' => 'ubicacion', 'codigo' => "{$bodega->codigo}/{$u->codigo}", 'creado' => $u->created_at], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                            <?php if($activa): ?>
                                                <?php
                                                    $accion = [
                                                        'url' => route('ubicaciones.inactivar', $u), 'metodo' => 'PATCH', 'peligro' => true,
                                                        'titulo' => '¿Inactivar la ubicación '.$u->codigo.'?',
                                                        'detalle' => 'Bodega '.$bodega->codigo.' · '.$bodega->nombre,
                                                        'puntos' => ['No se podrá ubicar mercancía en ella.', 'Solo se puede si no tiene pallets ubicados.', 'Podrá reactivarla cuando quiera.'],
                                                        'boton' => 'Inactivar ubicación',
                                                    ];
                                                ?>
                                                <button type="button" @click="$dispatch('confirmar', <?php echo e(Js::from($accion)); ?>)"
                                                        class="h-9 rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Inactivar</button>
                                            <?php else: ?>
                                                <form method="POST" action="<?php echo e(route('ubicaciones.activar', $u)); ?>">
                                                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                                    <button type="submit" class="h-9 rounded-lg border border-green-700 px-3 text-[13px] font-medium text-green-800 hover:bg-green-50">Reactivar</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/bodegas/show.blade.php ENDPATH**/ ?>
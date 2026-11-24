<?php use \App\Support\Milesimas; ?>
<?php use \Illuminate\Support\Js; ?>

<?php $__env->startSection('titulo', 'Activar aforo'); ?>

<?php $__env->startSection('migas'); ?>
    Entrada <span class="mx-1.5" aria-hidden="true">›</span>
    <a href="<?php echo e(route('aforos.index')); ?>" class="hover:underline">Aforos</a> <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Activar</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Activar aforo</h1>
        <p class="mt-1 text-sm text-slate-500">Elija un BL recibido y los bultos ubicados que se van a contar. Mientras dure el aforo, esos bultos no se mueven ni se legalizan.</p>
    </div>

    
    <form method="GET" action="<?php echo e(route('aforos.create')); ?>" class="flex flex-wrap items-end gap-3 rounded-[14px] border border-slate-200 bg-white p-5">
        <div class="flex min-w-72 flex-1 flex-col gap-1.5">
            <label for="bl" class="text-sm font-medium">BL</label>
            <select id="bl" name="bl" required class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                <option value="">Seleccione un BL recibido…</option>
                <?php $__currentLoopData = $bls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opcion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($opcion->id); ?>" <?php if($bl?->id === $opcion->id): echo 'selected'; endif; ?>><?php echo e($opcion->numero_bl); ?> · <?php echo e($opcion->cliente->codigo_corto); ?> · <?php echo e($opcion->fecha_ingreso->format('d/m/Y')); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php if($bls->isEmpty()): ?>
                <span class="text-xs text-slate-500">No hay BL recibidos sin un aforo en proceso.</span>
            <?php endif; ?>
        </div>
        <button type="submit" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Ver bultos</button>
    </form>

    
    <?php if($bl): ?>
        <?php
            $filas = $bultos->map(fn ($b) => [
                'id'        => $b->id,
                'reservada' => $b->detalles->contains(fn ($d) => Milesimas::de($d->cantidad_reservada) > 0),
            ]);
            $elegibles = $filas->reject(fn ($f) => $f['reservada'])->pluck('id')->values();
            $marcados = collect(old('bultos', $elegibles->all()))->map(fn ($id) => (int) $id)->values();
        ?>

        <form method="POST" action="<?php echo e(route('aforos.store')); ?>" class="rounded-[14px] border border-slate-200 bg-white"
              x-data="{ marcados: <?php echo e(Js::from($marcados)); ?>, elegibles: <?php echo e(Js::from($elegibles)); ?> }">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="bl_id" value="<?php echo e($bl->id); ?>">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div>
                    <h2 class="text-[15px] font-semibold">Bultos ubicados del BL <?php echo e($bl->numero_bl); ?></h2>
                    <p class="text-xs text-slate-500"><span x-text="marcados.length"></span> de <?php echo e($elegibles->count()); ?> elegidos</p>
                </div>
                <?php if($elegibles->isNotEmpty()): ?>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" class="rounded border-slate-300"
                               :checked="marcados.length === elegibles.length"
                               @change="marcados = $event.target.checked ? [...elegibles] : []">
                        Todos
                    </label>
                <?php endif; ?>
            </div>

            <?php if($bultos->isEmpty()): ?>
                <p class="p-5 text-sm text-slate-500">Este BL no tiene bultos UBICADOS. Ubíquelos desde la app antes de activar el aforo.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <th scope="col" class="w-12 px-5 py-3"><span class="sr-only">Elegir</span></th>
                                <th scope="col" class="px-3 py-3 font-semibold">Bulto</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Pallet</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Ubicación</th>
                                <th scope="col" class="px-3 py-3 font-semibold">Productos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $bultos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bulto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $reservada = $filas->firstWhere('id', $bulto->id)['reservada'];
                                    $lugar = $bulto->pallet?->bodega?->codigo.($bulto->pallet?->ubicacion ? ' / '.$bulto->pallet->ubicacion->codigo : '');
                                ?>
                                <tr class="<?php echo \Illuminate\Support\Arr::toCssClasses(['border-t border-slate-100', 'bg-slate-50 text-slate-500' => $reservada]); ?>">
                                    <td class="px-5 py-3">
                                        <input type="checkbox" name="bultos[]" value="<?php echo e($bulto->id); ?>" x-model.number="marcados"
                                               class="rounded border-slate-300" <?php if($reservada): echo 'disabled'; endif; ?> aria-label="Elegir <?php echo e($bulto->codigo); ?>">
                                    </td>
                                    <td class="px-3 py-3 font-mono text-[13px] font-semibold"><?php echo e($bulto->codigo); ?></td>
                                    <td class="px-3 py-3 font-mono text-[13px]"><?php echo e($bulto->pallet?->codigo); ?></td>
                                    <td class="px-3 py-3 font-mono text-[13px]"><?php echo e($lugar); ?></td>
                                    <td class="px-3 py-3">
                                        <?php $__currentLoopData = $bulto->detalles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $linea): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="tabular-nums"><?php echo e($linea->producto->sku); ?> · <?php echo e($linea->cantidad_recibida); ?> <?php echo e($linea->producto->unidad_medida->value); ?></div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($reservada): ?>
                                            <span class="text-xs text-acento-800">Tiene unidades reservadas: no puede entrar al aforo.</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php $__errorArgs = ['bultos'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="px-5 pt-3 text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            <div class="flex justify-end gap-2.5 border-t border-slate-200 px-5 py-4">
                <a href="<?php echo e(route('aforos.index')); ?>" class="inline-flex h-11 items-center rounded-[10px] border border-slate-300 px-5 text-sm font-medium hover:bg-slate-50">Cancelar</a>
                <button type="submit" :disabled="marcados.length === 0"
                        class="inline-flex h-11 items-center gap-2 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700 disabled:opacity-50">
                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'aforo','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'aforo','class' => 'size-[18px]']); ?>
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
                </button>
            </div>
        </form>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/aforos/create.blade.php ENDPATH**/ ?>
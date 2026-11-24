<?php $__env->startSection('titulo', 'Etiquetas'); ?>

<?php $__env->startSection('migas'); ?>
    Entrada <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Etiquetas</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Reimprimir etiqueta</h1>
        <p class="mt-1 text-sm text-slate-500">Genera la etiqueta en PDF (4 × 2 in). Toda reimpresión exige motivo y queda en la bitácora.</p>
    </div>

    <?php
        $claseCampo = 'h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20';
        $ayudas = [
            'bulto'     => 'Código del bulto: BUL-DDMMAA-0000',
            'pallet'    => 'Código del pallet: PLT-DDMMAA-0000',
            'bodega'    => 'Código de la bodega, tal como está en Maestros',
            'ubicacion' => 'Bodega y ubicación separadas por /: BOD-01/A-01-01',
        ];
    ?>

    <form method="POST" action="<?php echo e(route('etiquetas.reimprimir')); ?>" target="_blank"
          x-data="{ tipo: <?php echo e(Js::from(old('tipo', $tipos[0]))); ?>, motivo: <?php echo e(Js::from(old('motivo', ''))); ?>, ayudas: <?php echo e(Js::from($ayudas)); ?> }"
          class="flex max-w-2xl flex-col gap-5 rounded-[14px] border border-slate-200 bg-white p-6">
        <?php echo csrf_field(); ?>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="tipo" class="text-sm font-medium">Tipo de etiqueta</label>
                <select id="tipo" name="tipo" x-model="tipo" required class="<?php echo e($claseCampo); ?>">
                    <?php $__currentLoopData = $tipos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tipo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($tipo); ?>"><?php echo e(['bulto' => 'Bulto', 'pallet' => 'Pallet', 'bodega' => 'Bodega', 'ubicacion' => 'Ubicación'][$tipo]); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['tipo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="codigo" class="text-sm font-medium">Código</label>
                <input id="codigo" name="codigo" type="text" required maxlength="80" autocomplete="off" value="<?php echo e(old('codigo')); ?>"
                       class="<?php echo e($claseCampo); ?> font-mono" <?php $__errorArgs = ['codigo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
                <span class="text-xs text-slate-500" x-text="ayudas[tipo]"></span>
                <?php $__errorArgs = ['codigo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="motivo" class="text-sm font-medium">Motivo</label>
                <select id="motivo" name="motivo" x-model="motivo" required class="<?php echo e($claseCampo); ?>">
                    <option value="" disabled>Seleccione el motivo</option>
                    <?php $__currentLoopData = $motivos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $motivo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($motivo->value); ?>"><?php echo e($motivo->etiqueta()); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['motivo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="flex flex-col gap-1.5" x-show="motivo === 'OTRO'" x-cloak>
                <label for="detalle" class="text-sm font-medium">Detalle del motivo</label>
                <input id="detalle" name="detalle" type="text" maxlength="200" autocomplete="off" value="<?php echo e(old('detalle')); ?>"
                       :required="motivo === 'OTRO'" class="<?php echo e($claseCampo); ?>">
                <?php $__errorArgs = ['detalle'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-200 pt-5">
            <button type="submit" class="inline-flex h-11 items-center gap-2 rounded-[10px] bg-marca-600 px-5 text-sm font-semibold text-white hover:bg-marca-700">
                <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'etiqueta','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'etiqueta','class' => 'size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Generar PDF
            </button>
        </div>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/etiquetas/index.blade.php ENDPATH**/ ?>
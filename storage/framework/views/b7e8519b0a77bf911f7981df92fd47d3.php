
<?php use \App\Support\Etiquetas; ?>

<?php
    $usuario   = $usuario ?? null;
    $rolActual = old('rol', $usuario?->rol?->value);
    $esYo      = $usuario && auth()->user()->is($usuario);
?>

<div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4">
    <div class="flex flex-col gap-1.5">
        <label for="nombre" class="text-sm font-medium">Nombre completo</label>
        <input id="nombre" name="nombre" type="text" value="<?php echo e(old('nombre', $usuario?->nombre)); ?>" required maxlength="150"
               autocomplete="off" <?php $__errorArgs = ['nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
               class="h-[46px] rounded-[10px] border border-slate-300 px-3.5 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
        <?php $__errorArgs = ['nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="email" class="text-sm font-medium">Correo</label>
        <input id="email" name="email" type="email" value="<?php echo e(old('email', $usuario?->email)); ?>" required maxlength="150"
               autocomplete="off" inputmode="email" <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
               class="h-[46px] rounded-[10px] border border-slate-300 px-3.5 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
        <span class="text-xs text-slate-500">Será su usuario para ingresar.</span>
        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
</div>

<fieldset class="flex flex-col gap-2.5" <?php $__errorArgs = ['rol'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>>
    <legend class="mb-1.5 text-sm font-medium">Rol <span class="font-normal text-slate-500">(uno solo)</span></legend>

    <?php if($esYo): ?>
        <input type="hidden" name="rol" value="<?php echo e($usuario->rol->value); ?>">
        <p class="text-[13px] text-slate-500">No puede cambiar su propio rol.</p>
    <?php endif; ?>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-2.5">
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rol): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 p-3 has-checked:border-marca-600 has-checked:bg-marca-50 has-checked:ring-1 has-checked:ring-marca-600 has-disabled:cursor-not-allowed has-disabled:opacity-60">
                <input type="radio" name="rol" value="<?php echo e($rol->value); ?>" <?php if($rolActual === $rol->value): echo 'checked'; endif; ?> <?php if($esYo): echo 'disabled'; endif; ?>
                       required class="mt-0.5 accent-marca-600">
                <span>
                    <span class="block text-sm font-semibold"><?php echo e(Etiquetas::rol($rol)); ?></span>
                    <span class="mt-0.5 block text-[13px] text-slate-500"><?php echo e(Etiquetas::descripcionRol($rol)); ?></span>
                </span>
            </label>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php $__errorArgs = ['rol'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</fieldset>
<?php /**PATH E:\Kosmos\resources\views/usuarios/_form.blade.php ENDPATH**/ ?>
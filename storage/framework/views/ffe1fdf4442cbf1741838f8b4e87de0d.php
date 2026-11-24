<?php $__env->startSection('titulo', 'Ingresar'); ?>

<?php $__env->startSection('contenido'); ?>
    <h1 class="text-[26px] font-semibold tracking-tight">Bienvenido</h1>
    <p class="mt-1.5 text-[15px] text-slate-600">Ingrese con su correo corporativo.</p>

    <form method="POST" action="<?php echo e(route('login')); ?>" class="mt-7 flex flex-col gap-[18px]" novalidate>
        <?php echo csrf_field(); ?>

        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <div role="alert" class="flex items-start gap-2.5 rounded-[10px] border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-800">
                <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'error','class' => 'mt-px size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'error','class' => 'mt-px size-[18px]']); ?>
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
                <span><?php echo e($message); ?></span>
            </div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium">Correo</label>
            <input id="email" name="email" type="email" value="<?php echo e(old('email')); ?>" required autofocus
                   autocomplete="username" maxlength="150" inputmode="email"
                   <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                   class="h-[46px] rounded-[10px] border border-slate-300 px-3.5 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
        </div>

        <div x-data="{ ver: false }" class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium">Contraseña</label>
            <div class="relative">
                <input id="password" name="password" :type="ver ? 'text' : 'password'" type="password" required
                       autocomplete="current-password" maxlength="255"
                       class="h-[46px] w-full rounded-[10px] border border-slate-300 pl-3.5 pr-12 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
                <button type="button" @click="ver = ! ver" :aria-label="ver ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        aria-label="Mostrar contraseña"
                        class="absolute right-px top-px flex size-11 items-center justify-center text-slate-500 hover:text-slate-700">
                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'ojo','class' => 'size-5','xShow' => '! ver']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'ojo','class' => 'size-5','x-show' => '! ver']); ?>
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
                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'ojo-off','class' => 'size-5','xShow' => 'ver','xCloak' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'ojo-off','class' => 'size-5','x-show' => 'ver','x-cloak' => true]); ?>
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
                </button>
            </div>
        </div>

        <button type="submit" class="h-[46px] rounded-[10px] bg-marca-600 text-[15px] font-semibold text-white hover:bg-marca-700">
            Ingresar
        </button>
    </form>

    <p class="mt-7 text-[13px] leading-relaxed text-slate-500">
        ¿Olvidó su contraseña o su cuenta está bloqueada? Contacte al Administrador del Sistema.
    </p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/auth/login.blade.php ENDPATH**/ ?>
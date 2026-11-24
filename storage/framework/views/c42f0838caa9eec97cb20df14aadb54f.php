
<?php $bodega = $bodega ?? null; ?>

<div class="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-4">
    <?php if (isset($component)) { $__componentOriginalbb105f7b49c0e9718212460adae840e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb105f7b49c0e9718212460adae840e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'codigo','label' => 'Código','value' => $bodega?->codigo,'required' => true,'maxlength' => '20','autocomplete' => 'off','class' => 'uppercase','readonly' => (bool) $bodega,'placeholder' => 'BOD-A','ayuda' => $bodega ? 'No se puede cambiar: va impreso en la etiqueta.' : 'Letras, números o guion. Va impreso en la etiqueta.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'codigo','label' => 'Código','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bodega?->codigo),'required' => true,'maxlength' => '20','autocomplete' => 'off','class' => 'uppercase','readonly' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((bool) $bodega),'placeholder' => 'BOD-A','ayuda' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bodega ? 'No se puede cambiar: va impreso en la etiqueta.' : 'Letras, números o guion. Va impreso en la etiqueta.')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbb105f7b49c0e9718212460adae840e0)): ?>
<?php $attributes = $__attributesOriginalbb105f7b49c0e9718212460adae840e0; ?>
<?php unset($__attributesOriginalbb105f7b49c0e9718212460adae840e0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbb105f7b49c0e9718212460adae840e0)): ?>
<?php $component = $__componentOriginalbb105f7b49c0e9718212460adae840e0; ?>
<?php unset($__componentOriginalbb105f7b49c0e9718212460adae840e0); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginalbb105f7b49c0e9718212460adae840e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb105f7b49c0e9718212460adae840e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'nombre','label' => 'Nombre','value' => $bodega?->nombre,'required' => true,'maxlength' => '100','autocomplete' => 'off','placeholder' => 'Bodega principal']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'nombre','label' => 'Nombre','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bodega?->nombre),'required' => true,'maxlength' => '100','autocomplete' => 'off','placeholder' => 'Bodega principal']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbb105f7b49c0e9718212460adae840e0)): ?>
<?php $attributes = $__attributesOriginalbb105f7b49c0e9718212460adae840e0; ?>
<?php unset($__attributesOriginalbb105f7b49c0e9718212460adae840e0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbb105f7b49c0e9718212460adae840e0)): ?>
<?php $component = $__componentOriginalbb105f7b49c0e9718212460adae840e0; ?>
<?php unset($__componentOriginalbb105f7b49c0e9718212460adae840e0); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginalbb105f7b49c0e9718212460adae840e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb105f7b49c0e9718212460adae840e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'tamano_m2','label' => 'Tamaño (m²)','value' => $bodega?->tamano_m2,'required' => true,'inputmode' => 'decimal','autocomplete' => 'off','placeholder' => '1200','ayuda' => 'Base para calcular la ocupación.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'tamano_m2','label' => 'Tamaño (m²)','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bodega?->tamano_m2),'required' => true,'inputmode' => 'decimal','autocomplete' => 'off','placeholder' => '1200','ayuda' => 'Base para calcular la ocupación.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbb105f7b49c0e9718212460adae840e0)): ?>
<?php $attributes = $__attributesOriginalbb105f7b49c0e9718212460adae840e0; ?>
<?php unset($__attributesOriginalbb105f7b49c0e9718212460adae840e0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbb105f7b49c0e9718212460adae840e0)): ?>
<?php $component = $__componentOriginalbb105f7b49c0e9718212460adae840e0; ?>
<?php unset($__componentOriginalbb105f7b49c0e9718212460adae840e0); ?>
<?php endif; ?>
</div>

<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 has-checked:border-marca-600 has-checked:bg-marca-50">
    <input type="hidden" name="maneja_ubicaciones" value="0">
    <input type="checkbox" name="maneja_ubicaciones" value="1" class="mt-0.5 size-4 accent-marca-600"
           <?php if(old('maneja_ubicaciones', $bodega?->maneja_ubicaciones)): echo 'checked'; endif; ?>>
    <span>
        <span class="block text-sm font-semibold">Maneja ubicaciones internas</span>
        <span class="mt-0.5 block text-[13px] text-slate-500">
            Active si la bodega tiene posiciones codificadas (A-01, A-02…). Si no, al ubicar un pallet se escribe una referencia libre, como "junto a la puerta norte".
        </span>
    </span>
</label>
<?php $__errorArgs = ['maneja_ubicaciones'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
<?php /**PATH E:\Kosmos\resources\views/bodegas/_form.blade.php ENDPATH**/ ?>
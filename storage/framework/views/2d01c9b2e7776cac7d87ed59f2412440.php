
<?php $cliente = $cliente ?? null; ?>
<?php $editando = (bool) $cliente; ?>

<div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4">
    <?php if (isset($component)) { $__componentOriginalbb105f7b49c0e9718212460adae840e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb105f7b49c0e9718212460adae840e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'nit','label' => 'NIT','value' => $cliente?->nit,'required' => true,'maxlength' => '11','inputmode' => 'numeric','autocomplete' => 'off','readonly' => $editando,'placeholder' => '900123456','ayuda' => $editando ? 'No se puede cambiar.' : '9 dígitos, sin dígito de verificación.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'nit','label' => 'NIT','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cliente?->nit),'required' => true,'maxlength' => '11','inputmode' => 'numeric','autocomplete' => 'off','readonly' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editando),'placeholder' => '900123456','ayuda' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editando ? 'No se puede cambiar.' : '9 dígitos, sin dígito de verificación.')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'codigo_corto','label' => 'Código corto','value' => $cliente?->codigo_corto,'required' => true,'maxlength' => '6','autocomplete' => 'off','readonly' => $editando,'placeholder' => 'ACME','class' => 'uppercase','ayuda' => $editando ? 'No se puede cambiar: es el prefijo de sus SKU.' : '3 a 6 letras. Será el prefijo de sus SKU (ACME-00001) y no se podrá cambiar.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'codigo_corto','label' => 'Código corto','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cliente?->codigo_corto),'required' => true,'maxlength' => '6','autocomplete' => 'off','readonly' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editando),'placeholder' => 'ACME','class' => 'uppercase','ayuda' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($editando ? 'No se puede cambiar: es el prefijo de sus SKU.' : '3 a 6 letras. Será el prefijo de sus SKU (ACME-00001) y no se podrá cambiar.')]); ?>
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

<?php if (isset($component)) { $__componentOriginalbb105f7b49c0e9718212460adae840e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb105f7b49c0e9718212460adae840e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'razon_social','label' => 'Razón social','value' => $cliente?->razon_social,'required' => true,'maxlength' => '200','autocomplete' => 'organization']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'razon_social','label' => 'Razón social','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cliente?->razon_social),'required' => true,'maxlength' => '200','autocomplete' => 'organization']); ?>
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

<div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4">
    <?php if (isset($component)) { $__componentOriginalbb105f7b49c0e9718212460adae840e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb105f7b49c0e9718212460adae840e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'direccion','label' => 'Dirección','value' => $cliente?->direccion,'required' => true,'maxlength' => '255','autocomplete' => 'street-address']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'direccion','label' => 'Dirección','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cliente?->direccion),'required' => true,'maxlength' => '255','autocomplete' => 'street-address']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'telefono','label' => 'Teléfono','type' => 'tel','value' => $cliente?->telefono,'required' => true,'maxlength' => '30','autocomplete' => 'tel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'telefono','label' => 'Teléfono','type' => 'tel','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cliente?->telefono),'required' => true,'maxlength' => '30','autocomplete' => 'tel']); ?>
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

<?php if (isset($component)) { $__componentOriginalbb105f7b49c0e9718212460adae840e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb105f7b49c0e9718212460adae840e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'contacto_nombre','label' => 'Nombre de contacto','value' => $cliente?->contacto_nombre,'required' => true,'maxlength' => '150','autocomplete' => 'name']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'contacto_nombre','label' => 'Nombre de contacto','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cliente?->contacto_nombre),'required' => true,'maxlength' => '150','autocomplete' => 'name']); ?>
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
<?php /**PATH E:\Kosmos\resources\views/clientes/_form.blade.php ENDPATH**/ ?>
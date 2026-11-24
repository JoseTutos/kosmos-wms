
<?php use \App\Support\Etiquetas; ?>

<?php $producto = $producto ?? null; ?>

<?php if($producto): ?>
    <div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4">
        <?php if (isset($component)) { $__componentOriginalbb105f7b49c0e9718212460adae840e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb105f7b49c0e9718212460adae840e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'sku_lectura','label' => 'SKU','value' => $producto->sku,'readonly' => true,'ayuda' => 'Lo generó el sistema. No se puede cambiar.','class' => 'font-mono']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'sku_lectura','label' => 'SKU','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($producto->sku),'readonly' => true,'ayuda' => 'Lo generó el sistema. No se puede cambiar.','class' => 'font-mono']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'cliente_lectura','label' => 'Cliente','value' => $producto->cliente->codigo_corto.' · '.$producto->cliente->razon_social,'readonly' => true,'ayuda' => 'No se puede cambiar.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cliente_lectura','label' => 'Cliente','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($producto->cliente->codigo_corto.' · '.$producto->cliente->razon_social),'readonly' => true,'ayuda' => 'No se puede cambiar.']); ?>
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
<?php else: ?>
    <div class="flex flex-col gap-1.5">
        <label for="cliente_id" class="text-sm font-medium">Cliente</label>
        <select id="cliente_id" name="cliente_id" required <?php $__errorArgs = ['cliente_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            <option value="" disabled <?php if(! old('cliente_id')): echo 'selected'; endif; ?>>Seleccione un cliente activo</option>
            <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($cl->id); ?>" <?php if((string) old('cliente_id') === (string) $cl->id): echo 'selected'; endif; ?>><?php echo e($cl->codigo_corto); ?> · <?php echo e($cl->razon_social); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <span class="text-xs text-slate-500">El SKU se genera con su código corto: ACME-00001, ACME-00002…</span>
        <?php $__errorArgs = ['cliente_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
<?php endif; ?>

<?php if (isset($component)) { $__componentOriginalbb105f7b49c0e9718212460adae840e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb105f7b49c0e9718212460adae840e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'descripcion','label' => 'Descripción','value' => $producto?->descripcion,'required' => true,'maxlength' => '255','autocomplete' => 'off','placeholder' => 'Tornillo hexagonal M8 galvanizado']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'descripcion','label' => 'Descripción','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($producto?->descripcion),'required' => true,'maxlength' => '255','autocomplete' => 'off','placeholder' => 'Tornillo hexagonal M8 galvanizado']); ?>
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

<div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
    <div class="flex flex-col gap-1.5">
        <label for="tipo_almacenamiento" class="text-sm font-medium">Tipo de almacenamiento</label>
        <select id="tipo_almacenamiento" name="tipo_almacenamiento" required
                class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            <?php $__currentLoopData = $tipos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tipo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($tipo->value); ?>" <?php if(old('tipo_almacenamiento', $producto?->tipo_almacenamiento?->value) === $tipo->value): echo 'selected'; endif; ?>><?php echo e(Etiquetas::tipoAlmacenamiento($tipo)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['tipo_almacenamiento'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="unidad_medida" class="text-sm font-medium">Unidad de medida</label>
        <select id="unidad_medida" name="unidad_medida" required
                class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            <?php $__currentLoopData = $unidades; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unidad): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($unidad->value); ?>" <?php if(old('unidad_medida', $producto?->unidad_medida?->value) === $unidad->value): echo 'selected'; endif; ?>><?php echo e(Etiquetas::unidadMedida($unidad)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php if($producto): ?>
            <span class="text-xs text-slate-500">No se puede cambiar si ya hay mercancía registrada.</span>
        <?php endif; ?>
        <?php $__errorArgs = ['unidad_medida'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <?php if (isset($component)) { $__componentOriginalbb105f7b49c0e9718212460adae840e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbb105f7b49c0e9718212460adae840e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'peso_unitario_kg','label' => 'Peso unitario (kg)','value' => $producto?->peso_unitario_kg,'required' => true,'inputmode' => 'decimal','autocomplete' => 'off','placeholder' => '0,015','ayuda' => 'Hasta 3 decimales.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'peso_unitario_kg','label' => 'Peso unitario (kg)','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($producto?->peso_unitario_kg),'required' => true,'inputmode' => 'decimal','autocomplete' => 'off','placeholder' => '0,015','ayuda' => 'Hasta 3 decimales.']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.campo','data' => ['name' => 'stock_minimo','label' => 'Stock mínimo','value' => $producto?->stock_minimo,'inputmode' => 'decimal','autocomplete' => 'off','placeholder' => '0','ayuda' => 'Opcional. Solo es un aviso visual en el inventario.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('campo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'stock_minimo','label' => 'Stock mínimo','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($producto?->stock_minimo),'inputmode' => 'decimal','autocomplete' => 'off','placeholder' => '0','ayuda' => 'Opcional. Solo es un aviso visual en el inventario.']); ?>
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
<?php /**PATH E:\Kosmos\resources\views/productos/_form.blade.php ENDPATH**/ ?>
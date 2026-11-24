
<?php
    $bl = $bl ?? null;

    $filas = old('detalles', $bl
        ? $bl->detalles->map(fn ($d) => [
            'producto_id'        => (string) $d->producto_id,
            'bultos_esperados'   => (string) $d->bultos_esperados,
            'unidades_esperadas' => (string) $d->unidades_esperadas,
            'peso_bruto_kg'      => (string) $d->peso_bruto_kg,
        ])->values()->all()
        : []);

    if ($filas === []) {
        $filas = [['producto_id' => '', 'bultos_esperados' => '', 'unidades_esperadas' => '', 'peso_bruto_kg' => '']];
    }

    $clienteInicial = (string) old('cliente_id', $bl?->cliente_id);
    $erroresDetalle = collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, 'detalles'))->flatten();
    $claseCampo = 'h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px] outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20';
?>

<div x-data="{
        productos: <?php echo e(Js::from($productos)); ?>,
        cliente: <?php echo e(Js::from($clienteInicial)); ?>,
        filas: <?php echo e(Js::from(array_values($filas))); ?>,
        get delCliente() { return this.productos.filter(p => String(p.cliente_id) === String(this.cliente)); },
        usado(id, i) { return this.filas.some((f, j) => j !== i && String(f.producto_id) === String(id)); },
        unidad(id) { return (this.productos.find(p => String(p.id) === String(id)) || {}).unidad || ''; },
        agregar() { this.filas.push({ producto_id: '', bultos_esperados: '', unidades_esperadas: '', peso_bruto_kg: '' }); },
        quitar(i) { this.filas.splice(i, 1); if (this.filas.length === 0) this.agregar(); },
        cambiarCliente() { this.filas = [{ producto_id: '', bultos_esperados: '', unidades_esperadas: '', peso_bruto_kg: '' }]; }
     }" class="flex flex-col gap-5">

    
    <div class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4">
        <div class="flex flex-col gap-1.5">
            <label for="numero_bl" class="text-sm font-medium">Número de BL</label>
            <input id="numero_bl" name="numero_bl" type="text" required minlength="4" maxlength="30" autocomplete="off"
                   value="<?php echo e(old('numero_bl', $bl?->numero_bl)); ?>" placeholder="MSCU1234567"
                   <?php $__errorArgs = ['numero_bl'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                   class="<?php echo e($claseCampo); ?> font-mono uppercase">
            <span class="text-xs text-slate-500">Se guarda en mayúsculas y sin espacios.</span>
            <?php $__errorArgs = ['numero_bl'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <?php if($bl): ?>
            <div class="flex flex-col gap-1.5">
                <label for="cliente_lectura" class="text-sm font-medium">Cliente</label>
                <input id="cliente_lectura" type="text" readonly value="<?php echo e($bl->cliente->codigo_corto); ?> · <?php echo e($bl->cliente->razon_social); ?>"
                       class="h-[46px] rounded-[10px] border border-slate-200 bg-fondo px-3 text-[15px] text-slate-600">
                <span class="text-xs text-slate-500">No se puede cambiar: define los productos del manifiesto.</span>
            </div>
        <?php else: ?>
            <div class="flex flex-col gap-1.5">
                <label for="cliente_id" class="text-sm font-medium">Cliente</label>
                <select id="cliente_id" name="cliente_id" required x-model="cliente" @change="cambiarCliente()"
                        <?php $__errorArgs = ['cliente_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> class="<?php echo e($claseCampo); ?>">
                    <option value="" disabled>Seleccione un cliente activo</option>
                    <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($cl->id); ?>"><?php echo e($cl->codigo_corto); ?> · <?php echo e($cl->razon_social); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
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

        <div class="flex flex-col gap-1.5">
            <label for="fecha_ingreso" class="text-sm font-medium">Fecha de ingreso</label>
            <input id="fecha_ingreso" name="fecha_ingreso" type="date" required
                   value="<?php echo e(old('fecha_ingreso', $bl?->fecha_ingreso?->format('Y-m-d'))); ?>"
                   <?php $__errorArgs = ['fecha_ingreso'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> class="<?php echo e($claseCampo); ?>">
            <span class="text-xs text-slate-500">Fecha esperada de llegada.</span>
            <?php $__errorArgs = ['fecha_ingreso'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="factura_comercial" class="text-sm font-medium">Factura comercial</label>
            <input id="factura_comercial" name="factura_comercial" type="text" required maxlength="50" autocomplete="off"
                   value="<?php echo e(old('factura_comercial', $bl?->factura_comercial)); ?>" placeholder="FC-2026-0815"
                   <?php $__errorArgs = ['factura_comercial'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> aria-invalid="true" <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> class="<?php echo e($claseCampo); ?>">
            <?php $__errorArgs = ['factura_comercial'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </div>

    
    <fieldset class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4 rounded-xl border border-slate-200 p-4">
        <legend class="px-1 text-sm font-medium">Preinspección <span class="font-normal text-slate-500">(opcional)</span></legend>

        <div class="flex flex-col gap-1.5">
            <label for="fecha_preinspeccion" class="text-sm font-medium">Fecha</label>
            <input id="fecha_preinspeccion" name="fecha_preinspeccion" type="date" max="<?php echo e(now()->format('Y-m-d')); ?>"
                   value="<?php echo e(old('fecha_preinspeccion', $bl?->fecha_preinspeccion?->format('Y-m-d'))); ?>" class="<?php echo e($claseCampo); ?>">
            <?php $__errorArgs = ['fecha_preinspeccion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="acta" class="text-sm font-medium">Acta</label>
            <input id="acta" name="acta" type="file" accept=".pdf,.jpg,.jpeg,.png"
                   class="rounded-[10px] border border-slate-300 bg-white p-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm">
            <span class="text-xs text-slate-500">PDF o imagen, máximo 10 MB.</span>
            <?php if($bl?->acta_preinspeccion_path): ?>
                <span class="flex flex-wrap items-center gap-3 text-xs">
                    <a href="<?php echo e(route('bls.acta', $bl)); ?>" class="text-marca-600 hover:underline">Ver acta actual</a>
                    <label class="inline-flex items-center gap-1.5 text-slate-600">
                        <input type="checkbox" name="quitar_acta" value="1" class="rounded border-slate-300"> Quitar acta
                    </label>
                </span>
            <?php endif; ?>
            <?php $__errorArgs = ['acta'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-sm text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
    </fieldset>

    
    <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="text-base font-semibold">Manifiesto</h2>
                <p class="text-xs text-slate-500">Un renglón por producto. Solo productos activos del cliente.</p>
            </div>
            <button type="button" @click="agregar()" :disabled="! cliente"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
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
<?php endif; ?> Agregar producto
            </button>
        </div>

        <?php if($erroresDetalle->isNotEmpty()): ?>
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc space-y-0.5 pl-5">
                    <?php $__currentLoopData = $erroresDetalle->unique(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mensaje): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($mensaje); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <p x-show="! cliente" class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
            Seleccione el cliente para elegir sus productos.
        </p>

        <div x-show="cliente" class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full min-w-[760px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-3 py-2.5 font-semibold">Producto</th>
                        <th scope="col" class="w-32 px-3 py-2.5 text-right font-semibold">Bultos</th>
                        <th scope="col" class="w-40 px-3 py-2.5 text-right font-semibold">Unidades</th>
                        <th scope="col" class="w-40 px-3 py-2.5 text-right font-semibold">Peso bruto (kg)</th>
                        <th scope="col" class="w-12 px-3 py-2.5"><span class="sr-only">Quitar</span></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(fila, i) in filas" :key="i">
                        <tr class="border-t border-slate-100 align-top">
                            <td class="px-3 py-2">
                                <select :name="`detalles[${i}][producto_id]`" x-model="fila.producto_id" required
                                        :aria-label="`Producto del renglón ${i + 1}`"
                                        class="h-10 w-full rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                                    <option value="">Seleccione…</option>
                                    <template x-for="p in delCliente" :key="p.id">
                                        <option :value="p.id" x-text="p.texto" :selected="String(p.id) === String(fila.producto_id)" :disabled="usado(p.id, i)"></option>
                                    </template>
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" min="1" step="1" required :name="`detalles[${i}][bultos_esperados]`" x-model="fila.bultos_esperados"
                                       :aria-label="`Bultos del renglón ${i + 1}`"
                                       class="h-10 w-full rounded-[10px] border border-slate-300 px-2.5 text-right text-sm tabular-nums">
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-1.5">
                                    <input type="text" inputmode="decimal" required :name="`detalles[${i}][unidades_esperadas]`" x-model="fila.unidades_esperadas"
                                           :aria-label="`Unidades del renglón ${i + 1}`" placeholder="0"
                                           class="h-10 w-full rounded-[10px] border border-slate-300 px-2.5 text-right text-sm tabular-nums">
                                    <span class="w-8 text-xs text-slate-500" x-text="unidad(fila.producto_id)"></span>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" inputmode="decimal" required :name="`detalles[${i}][peso_bruto_kg]`" x-model="fila.peso_bruto_kg"
                                       :aria-label="`Peso del renglón ${i + 1}`" placeholder="0,000"
                                       class="h-10 w-full rounded-[10px] border border-slate-300 px-2.5 text-right text-sm tabular-nums">
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" @click="quitar(i)" :aria-label="`Quitar el renglón ${i + 1}`"
                                        class="inline-flex size-10 items-center justify-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-700">
                                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'papelera','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'papelera','class' => 'size-[18px]']); ?>
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
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-500">Decimales con coma o punto, hasta 3 (0,015). Peso y unidades deben ser mayores que cero.</p>
    </div>
</div>
<?php /**PATH E:\Kosmos\resources\views/bls/_form.blade.php ENDPATH**/ ?>
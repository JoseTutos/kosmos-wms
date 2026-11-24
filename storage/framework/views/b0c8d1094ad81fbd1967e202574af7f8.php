
<div x-data="{
        abierto: false,
        a: {},
        abrir(datos) { this.a = datos; this.abierto = true; this.$nextTick(() => this.$refs.cancelar.focus()); }
     }"
     @confirmar.window="abrir($event.detail)"
     @keydown.escape.window="abierto = false"
     x-show="abierto" x-cloak x-transition.opacity
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-6">

    <div role="dialog" aria-modal="true" aria-labelledby="confirmar-titulo"
         class="flex w-full max-w-[480px] flex-col gap-5 rounded-2xl bg-white p-7 shadow-xl">
        <div class="flex items-start gap-3.5">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full"
                  :class="a.peligro ? 'bg-red-100 text-red-700' : 'bg-marca-100 text-marca-600'">
                <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'alerta','class' => 'size-[22px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'alerta','class' => 'size-[22px]']); ?>
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
            </span>
            <div>
                <h2 id="confirmar-titulo" class="text-lg font-semibold tracking-tight" x-text="a.titulo"></h2>
                <p class="mt-1 text-sm text-slate-500" x-text="a.detalle"></p>
            </div>
        </div>

        <ul class="list-disc space-y-1 pl-5 text-sm leading-relaxed text-slate-700">
            <template x-for="punto in (a.puntos || [])" :key="punto">
                <li x-text="punto"></li>
            </template>
        </ul>

        <form method="POST" :action="a.url" class="flex flex-wrap justify-end gap-2.5">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="_method" :value="a.metodo || 'POST'">
            <button type="button" x-ref="cancelar" @click="abierto = false"
                    class="h-11 rounded-[10px] border border-slate-300 px-[18px] text-sm font-medium hover:bg-slate-50">Cancelar</button>
            <button type="submit" x-text="a.boton"
                    :class="a.peligro ? 'bg-red-700 hover:bg-red-800' : 'bg-marca-600 hover:bg-marca-700'"
                    class="h-11 rounded-[10px] px-[18px] text-sm font-semibold text-white"></button>
        </form>
    </div>
</div>
<?php /**PATH E:\Kosmos\resources\views/components/modal-confirmacion.blade.php ENDPATH**/ ?>
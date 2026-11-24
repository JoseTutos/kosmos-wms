<?php use \App\Enums\EstadoUsuario; ?>
<?php use \App\Support\Etiquetas; ?>
<?php use \Illuminate\Support\Js; ?>

<?php $__env->startSection('titulo', 'Usuarios'); ?>
<?php $__env->startSection('migas'); ?>
    Administración <span class="mx-1.5" aria-hidden="true">›</span> <span class="font-medium text-slate-800">Usuarios</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
<?php
    $yo = auth()->user();
?>

<div x-data="{
        abierto: false,
        a: {},
        confirmacion: '',
        abrir(accion) {
            this.a = accion;
            this.confirmacion = '';
            this.abierto = true;
            this.$nextTick(() => (accion.correo ? this.$refs.correo : this.$refs.cancelar).focus());
        },
        get habilitado() {
            return ! this.a.correo || this.confirmacion.trim().toLowerCase() === this.a.correo;
        }
     }"
     @keydown.escape.window="abierto = false"
     class="flex flex-col gap-[18px]">

    
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Usuarios</h1>
            <p class="mt-1 text-sm text-slate-500">Un rol por usuario. Los usuarios con historial se inactivan, no se eliminan.</p>
        </div>
        <a href="<?php echo e(route('usuarios.create')); ?>"
           class="inline-flex h-[42px] items-center gap-2 rounded-[10px] bg-marca-600 px-4 text-sm font-semibold text-white hover:bg-marca-700">
            <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'mas','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'mas','class' => 'size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Nuevo usuario
        </a>
    </div>

    <div class="rounded-[14px] border border-slate-200 bg-white">

        
        <form method="GET" action="<?php echo e(route('usuarios.index')); ?>" role="search"
              class="flex flex-wrap items-end gap-3 border-b border-slate-200 p-4">
            <div class="flex min-w-60 flex-1 flex-col gap-1.5">
                <label for="q" class="text-xs font-medium text-slate-500">Buscar</label>
                <input id="q" name="q" type="search" value="<?php echo e(request('q')); ?>" maxlength="100" placeholder="Nombre o correo"
                       class="h-10 rounded-[10px] border border-slate-300 px-3 text-sm outline-none focus:border-marca-600 focus:ring-2 focus:ring-marca-600/20">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="rol" class="text-xs font-medium text-slate-500">Rol</label>
                <select id="rol" name="rol" class="h-10 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rol): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($rol->value); ?>" <?php if(request('rol') === $rol->value): echo 'selected'; endif; ?>><?php echo e(Etiquetas::rolCorto($rol)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="estado" class="text-xs font-medium text-slate-500">Estado</label>
                <select id="estado" name="estado" class="h-10 rounded-[10px] border border-slate-300 bg-white px-2.5 text-sm">
                    <option value="">Todos</option>
                    <?php $__currentLoopData = $estados; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $estado): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($estado->value); ?>" <?php if(request('estado') === $estado->value): echo 'selected'; endif; ?>><?php echo e(Etiquetas::estado($estado)); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button type="submit" class="h-10 rounded-[10px] bg-slate-800 px-4 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
            <?php if(request()->hasAny(['q', 'rol', 'estado'])): ?>
                <a href="<?php echo e(route('usuarios.index')); ?>" class="h-10 px-2 text-sm leading-10 text-marca-600 hover:underline">Limpiar</a>
            <?php endif; ?>
        </form>

        
        <div class="overflow-x-auto lg:overflow-visible">
            <table class="w-full min-w-[760px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Usuario</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Rol</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 text-center font-semibold">Intentos</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $usuarios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $esYo      = $yo->is($u);
                        $bloqueado = $u->estado === EstadoUsuario::BLOQUEADO;
                        $inactivo  = $u->estado === EstadoUsuario::INACTIVO;
                        $activo    = ! $bloqueado && ! $inactivo;
                        $sinOperaciones = ! ($u->movimientos_exists || $u->legalizaciones_exists || $u->ajustes_exists || $u->auditorias_exists);
                        $detalle   = $u->email.' · '.Etiquetas::rol($u->rol);

                        // Textos de cada ventana de confirmación (se pasan escapados con Js::from)
                        $acciones = [
                            'reset' => [
                                'url' => route('usuarios.reset-password', $u), 'metodo' => 'PATCH', 'icono' => 'llave', 'peligro' => false,
                                'titulo' => "¿Restablecer la contraseña de {$u->nombre}?",
                                'detalle' => $detalle,
                                'puntos' => ['Su contraseña actual dejará de funcionar.', 'Se generará una temporal que verá una sola vez.', 'Su sesión en la app móvil se cerrará.'],
                                'boton' => 'Restablecer contraseña',
                            ],
                            'inactivar' => [
                                'url' => route('usuarios.inactivar', $u), 'metodo' => 'PATCH', 'icono' => 'prohibido', 'peligro' => true,
                                'titulo' => "¿Inactivar a {$u->nombre}?",
                                'detalle' => $detalle,
                                'puntos' => ['No podrá ingresar a la web ni a la app móvil.', 'Su sesión abierta se cerrará de inmediato.', 'Su historial se conserva; podrá reactivarlo después.'],
                                'boton' => 'Inactivar usuario',
                            ],
                            'bloquear' => [
                                'url' => route('usuarios.bloquear', $u), 'metodo' => 'PATCH', 'icono' => 'candado', 'peligro' => true,
                                'titulo' => "¿Bloquear a {$u->nombre}?",
                                'detalle' => $detalle,
                                'puntos' => ['No podrá ingresar a la web ni a la app móvil.', 'Su sesión abierta se cerrará de inmediato.', 'Podrá desbloquearlo después desde este listado.'],
                                'boton' => 'Bloquear usuario',
                            ],
                            'eliminar' => [
                                'url' => route('usuarios.destroy', $u), 'metodo' => 'DELETE', 'icono' => 'papelera', 'peligro' => true,
                                'titulo' => "¿Eliminar a {$u->nombre}?",
                                'detalle' => $detalle.' · Bloqueado',
                                'puntos' => ['Esta acción no se puede deshacer.', 'Se permite porque está bloqueado y no tiene operaciones registradas.', 'La bitácora conservará el registro de la eliminación.'],
                                'boton' => 'Eliminar definitivamente',
                                'correo' => $u->email,
                            ],
                        ];
                    ?>

                    <tr class="<?php echo \Illuminate\Support\Arr::toCssClasses(['border-t border-slate-100', 'bg-red-50/40' => $bloqueado]); ?>">
                        
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                              'bg-red-100 text-red-800' => $bloqueado,
                                              'bg-slate-100 text-slate-500' => $inactivo,
                                              'bg-marca-100 text-marca-900' => $activo]); ?>"><?php echo e(Etiquetas::iniciales($u->nombre)); ?></span>
                                <span>
                                    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['block font-medium', 'text-slate-500' => $inactivo]); ?>">
                                        <?php echo e($u->nombre); ?>

                                        <?php if($esYo): ?>
                                            <span class="font-normal text-slate-500">(usted)</span>
                                        <?php endif; ?>
                                        <?php if($u->debe_cambiar_password): ?>
                                            <span class="ml-1 rounded-full bg-yellow-100 px-2 py-0.5 text-[11px] font-semibold text-yellow-900">Clave temporal</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="block text-[13px] text-slate-500"><?php echo e($u->email); ?></span>
                                </span>
                            </div>
                        </td>

                        
                        <td class="<?php echo \Illuminate\Support\Arr::toCssClasses(['px-4 py-3', 'text-slate-500' => $inactivo]); ?>"><?php echo e(Etiquetas::rolCorto($u->rol)); ?></td>

                        
                        <td class="px-4 py-3">
                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                          'bg-green-100 text-green-800' => $activo,
                                          'bg-red-100 text-red-800' => $bloqueado,
                                          'bg-slate-200 text-slate-700' => $inactivo]); ?>">
                                <?php if($bloqueado): ?>
                                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'candado','class' => 'size-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'candado','class' => 'size-3']); ?>
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
                                <?php else: ?>
                                    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['size-1.5 rounded-full', 'bg-green-600' => ! $inactivo, 'bg-slate-500' => $inactivo]); ?>"></span>
                                <?php endif; ?>
                                <?php echo e(Etiquetas::estado($u->estado)); ?>

                            </span>
                        </td>

                        
                        <td class="<?php echo \Illuminate\Support\Arr::toCssClasses(['px-4 py-3 text-center', 'font-semibold text-red-800' => $bloqueado, 'text-slate-500' => $inactivo]); ?>"><?php echo e($u->intentos_fallidos); ?></td>

                        
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">

                                
                                <?php if(! $activo): ?>
                                    <form method="POST" action="<?php echo e(route('usuarios.activar', $u)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <button type="submit" class="h-9 rounded-lg border border-green-700 px-3 text-[13px] font-medium text-green-800 hover:bg-green-50">
                                            <?php echo e($bloqueado ? 'Desbloquear' : 'Reactivar'); ?>

                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="<?php echo e(route('usuarios.edit', $u)); ?>" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-[13px] hover:bg-slate-50">Editar</a>
                                <?php endif; ?>

                                
                                <?php if (! ($esYo)): ?>
                                    <div x-data="{ menu: false }" class="relative" @keydown.escape.stop="menu = false">
                                        <button type="button" @click="menu = ! menu" :aria-expanded="menu" aria-haspopup="menu"
                                                :class="menu ? 'border-marca-600 text-marca-600' : 'border-slate-300'"
                                                class="inline-flex h-9 items-center gap-1 rounded-lg border px-2.5 text-[13px] hover:bg-slate-50">
                                            Más <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'abajo','class' => 'size-4 transition',':class' => 'menu && \'rotate-180\'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'abajo','class' => 'size-4 transition',':class' => 'menu && \'rotate-180\'']); ?>
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

                                        <div x-show="menu" x-cloak x-transition.origin.top.right @click.outside="menu = false" role="menu"
                                             class="absolute right-0 z-20 mt-1.5 w-[250px] rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">

                                            <?php if(! $activo): ?>
                                                <a href="<?php echo e(route('usuarios.edit', $u)); ?>" role="menuitem" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm hover:bg-slate-50">
                                                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'editar','class' => 'size-[18px] text-slate-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'editar','class' => 'size-[18px] text-slate-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Editar
                                                </a>
                                            <?php endif; ?>

                                            <button type="button" role="menuitem" @click="menu = false; abrir(<?php echo e(Js::from($acciones['reset'])); ?>)"
                                                    class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm hover:bg-slate-50">
                                                <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'llave','class' => 'size-[18px] text-slate-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'llave','class' => 'size-[18px] text-slate-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Restablecer contraseña
                                            </button>

                                            <?php if (! ($inactivo)): ?>
                                                <button type="button" role="menuitem" @click="menu = false; abrir(<?php echo e(Js::from($acciones['inactivar'])); ?>)"
                                                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm hover:bg-slate-50">
                                                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'prohibido','class' => 'size-[18px] text-slate-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'prohibido','class' => 'size-[18px] text-slate-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Inactivar usuario
                                                </button>
                                            <?php endif; ?>

                                            <?php if($activo): ?>
                                                <div class="my-1 h-px bg-slate-200"></div>
                                                <button type="button" role="menuitem" @click="menu = false; abrir(<?php echo e(Js::from($acciones['bloquear'])); ?>)"
                                                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm text-red-700 hover:bg-red-50">
                                                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'candado','class' => 'size-[18px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'candado','class' => 'size-[18px]']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $attributes = $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6)): ?>
<?php $component = $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6; ?>
<?php unset($__componentOriginal1cc77f99ef34ed73061e695fbeb001f6); ?>
<?php endif; ?> Bloquear usuario
                                                </button>
                                            <?php endif; ?>

                                            <?php if($bloqueado): ?>
                                                <div class="my-1 h-px bg-slate-200"></div>
                                                <?php if($sinOperaciones): ?>
                                                    <button type="button" role="menuitem" @click="menu = false; abrir(<?php echo e(Js::from($acciones['eliminar'])); ?>)"
                                                            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm text-red-700 hover:bg-red-50">
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
<?php endif; ?> Eliminar usuario
                                                    </button>
                                                <?php else: ?>
                                                    <p class="px-2.5 py-2 text-xs leading-snug text-slate-500">
                                                        Tiene operaciones registradas: no se puede eliminar. Puede dejarlo bloqueado o inactivarlo.
                                                    </p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
                            No hay usuarios con esos filtros.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        <?php if($usuarios->hasPages()): ?>
            <div class="border-t border-slate-200 px-4 py-3"><?php echo e($usuarios->links()); ?></div>
        <?php else: ?>
            <p class="border-t border-slate-200 px-4 py-3 text-[13px] text-slate-500">
                Mostrando <?php echo e($usuarios->count()); ?> <?php echo e($usuarios->count() === 1 ? 'usuario' : 'usuarios'); ?>

            </p>
        <?php endif; ?>
    </div>

    
    <div x-show="abierto" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-6">
        <div role="dialog" aria-modal="true" aria-labelledby="titulo-confirmar"
             class="flex w-full max-w-[480px] flex-col gap-5 rounded-2xl bg-white p-7 shadow-xl">
            <div class="flex items-start gap-3.5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full"
                      :class="a.peligro ? 'bg-red-100 text-red-700' : 'bg-marca-100 text-marca-600'">
                    <?php if (isset($component)) { $__componentOriginal1cc77f99ef34ed73061e695fbeb001f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cc77f99ef34ed73061e695fbeb001f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'candado','class' => 'size-[22px]','xShow' => 'a.icono === \'candado\'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'candado','class' => 'size-[22px]','x-show' => 'a.icono === \'candado\'']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'papelera','class' => 'size-[22px]','xShow' => 'a.icono === \'papelera\'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'papelera','class' => 'size-[22px]','x-show' => 'a.icono === \'papelera\'']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'prohibido','class' => 'size-[22px]','xShow' => 'a.icono === \'prohibido\'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'prohibido','class' => 'size-[22px]','x-show' => 'a.icono === \'prohibido\'']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icono','data' => ['nombre' => 'llave','class' => 'size-[22px]','xShow' => 'a.icono === \'llave\'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icono'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nombre' => 'llave','class' => 'size-[22px]','x-show' => 'a.icono === \'llave\'']); ?>
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
                    <h2 id="titulo-confirmar" class="text-lg font-semibold tracking-tight" x-text="a.titulo"></h2>
                    <p class="mt-1 text-sm text-slate-500" x-text="a.detalle"></p>
                </div>
            </div>

            <ul class="list-disc space-y-1 pl-5 text-sm leading-relaxed text-slate-700">
                <template x-for="punto in (a.puntos || [])" :key="punto">
                    <li x-text="punto"></li>
                </template>
            </ul>

            <form method="POST" :action="a.url" class="flex flex-col gap-5">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="_method" :value="a.metodo">

                
                <template x-if="a.correo">
                    <div class="flex flex-col gap-1.5">
                        <label for="confirmacion" class="text-sm font-medium">Escriba el correo del usuario para confirmar</label>
                        <input id="confirmacion" name="confirmacion" type="text" x-ref="correo" x-model="confirmacion"
                               :placeholder="a.correo" autocomplete="off" autocapitalize="off" spellcheck="false"
                               class="h-[46px] rounded-[10px] border border-slate-300 px-3.5 text-[15px] outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                    </div>
                </template>

                <div class="flex flex-wrap justify-end gap-2.5">
                    <button type="button" x-ref="cancelar" @click="abierto = false"
                            class="h-11 rounded-[10px] border border-slate-300 px-[18px] text-sm font-medium hover:bg-slate-50">Cancelar</button>
                    <button type="submit" :disabled="! habilitado" x-text="a.boton"
                            :class="a.peligro ? 'bg-red-700 hover:bg-red-800' : 'bg-marca-600 hover:bg-marca-700'"
                            class="h-11 rounded-[10px] px-[18px] text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/usuarios/index.blade.php ENDPATH**/ ?>
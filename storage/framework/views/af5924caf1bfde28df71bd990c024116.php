<?php use \App\Services\Control\EstadoLegalService; ?>
<?php use \App\Support\Milesimas; ?>

<?php $__env->startSection('titulo', 'Estado legal'); ?>

<?php $__env->startSection('migas'); ?>
    Control y legalización <span class="mx-1.5" aria-hidden="true">›</span>
    <span class="font-medium text-slate-800">Estado legal</span>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('contenido'); ?>
    <?php
        $indicadores = [
            EstadoLegalService::INDICADOR_TOTAL   => ['Cuarentena total', 'bg-red-600', 'text-red-700'],
            EstadoLegalService::INDICADOR_PARCIAL => ['Cuarentena parcial', 'bg-yellow-400', 'text-yellow-800'],
            EstadoLegalService::INDICADOR_AFORO   => ['En aforo', 'bg-marca-600', 'text-marca-700'],
        ];
    ?>

    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Estado legal activo</h1>
        <p class="mt-1 text-sm text-slate-500">BL con unidades en cuarentena, de más días a menos. Un BL legalizado por completo sale de esta lista.</p>
    </div>

    <div class="flex flex-wrap gap-4 text-xs text-slate-600" aria-label="Convenciones">
        <?php $__currentLoopData = $indicadores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$texto, $punto]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full <?php echo e($punto); ?>"></span><?php echo e($texto); ?></span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <form method="GET" action="<?php echo e(route('estado-legal.index')); ?>" class="flex flex-wrap items-end gap-3 rounded-[14px] border border-slate-200 bg-white p-5">
        <div class="flex min-w-56 flex-col gap-1.5">
            <label for="cliente_id" class="text-sm font-medium">Cliente</label>
            <select id="cliente_id" name="cliente_id" class="h-[46px] rounded-[10px] border border-slate-300 bg-white px-3 text-[15px]">
                <option value="">Todos</option>
                <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cliente): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($cliente->id); ?>" <?php if((string) request('cliente_id') === (string) $cliente->id): echo 'selected'; endif; ?>><?php echo e($cliente->codigo_corto); ?> · <?php echo e($cliente->razon_social); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="flex min-w-48 flex-1 flex-col gap-1.5">
            <label for="bl" class="text-sm font-medium">BL</label>
            <input id="bl" name="bl" type="search" value="<?php echo e(request('bl')); ?>" maxlength="30" class="h-[46px] rounded-[10px] border border-slate-300 px-3 font-mono text-[15px]">
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="dias_min" class="text-sm font-medium">Días desde</label>
            <input id="dias_min" name="dias_min" type="number" min="0" value="<?php echo e(request('dias_min')); ?>" class="h-[46px] w-28 rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="dias_max" class="text-sm font-medium">Días hasta</label>
            <input id="dias_max" name="dias_max" type="number" min="0" value="<?php echo e(request('dias_max')); ?>" class="h-[46px] w-28 rounded-[10px] border border-slate-300 px-3 text-[15px]">
        </div>
        <button type="submit" class="h-[46px] rounded-[10px] bg-slate-800 px-5 text-sm font-medium text-white hover:bg-slate-700">Filtrar</button>
        <?php if(request()->hasAny(['cliente_id', 'bl', 'dias_min', 'dias_max'])): ?>
            <a href="<?php echo e(route('estado-legal.index')); ?>" class="inline-flex h-[46px] items-center rounded-[10px] border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="rounded-[14px] border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Cliente</th>
                        <th scope="col" class="px-4 py-3 font-semibold">BL</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Ingreso</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Días</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Preinspección</th>
                        <th scope="col" class="px-4 py-3 text-right font-semibold">Discrepancia</th>
                        <th scope="col" class="px-4 py-3 font-semibold">En cuarentena / total</th>
                    </tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $bls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fila): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        [$texto, $punto, $color] = $indicadores[EstadoLegalService::indicador($fila)];
                        $total = Milesimas::de((string) $fila->total_recibida);
                        $cuarentena = Milesimas::de((string) $fila->total_cuarentena);
                        $pct = $total > 0 ? (int) round($cuarentena * 100 / $total) : 0;
                        $discrepancia = (int) $fila->discrepancia_bultos;
                    ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2 text-xs font-semibold <?php echo e($color); ?>"><span class="size-2.5 rounded-full <?php echo e($punto); ?>"></span><?php echo e($texto); ?></span>
                        </td>
                        <td class="px-4 py-3" title="<?php echo e($fila->cliente->razon_social); ?>"><?php echo e($fila->cliente->codigo_corto); ?></td>
                        <td class="px-4 py-3">
                            <a href="<?php echo e(route('estado-legal.show', $fila)); ?>" class="font-mono text-[13px] font-semibold text-marca-600 hover:underline"><?php echo e($fila->numero_bl); ?></a>
                        </td>
                        <td class="px-4 py-3 tabular-nums"><?php echo e($fila->fecha_ingreso->format('d/m/Y')); ?></td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums"><?php echo e((int) $fila->dias); ?></td>
                        <td class="px-4 py-3"><?php echo e($fila->fecha_preinspeccion?->format('d/m/Y') ?? 'Sin preinspección'); ?></td>
                        <td class="<?php echo \Illuminate\Support\Arr::toCssClasses(['px-4 py-3 text-right tabular-nums', 'font-semibold text-acento-800' => $discrepancia !== 0, 'text-slate-400' => $discrepancia === 0]); ?>">
                            <?php echo e($discrepancia > 0 ? '+' : ''); ?><?php echo e($discrepancia); ?> bultos
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-2 w-28 rounded-full bg-slate-100" role="progressbar" aria-valuenow="<?php echo e($pct); ?>" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-2 rounded-full <?php echo e($punto); ?>" style="width: <?php echo e($pct); ?>%"></div>
                                </div>
                                <span class="tabular-nums"><?php echo e($fila->total_cuarentena); ?> <span class="text-slate-500">/ <?php echo e($fila->total_recibida); ?></span></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No hay BL con unidades en cuarentena con estos filtros.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php echo e($bls->links()); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Kosmos\resources\views/estado-legal/index.blade.php ENDPATH**/ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Diferencias aforo AFO-<?php echo e($aforo->id); ?></title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .sub { color: #475569; margin-bottom: 10px; }
        .resumen td { padding: 2px 14px 2px 0; }
        table.lineas { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.lineas th { background: #e2e8f0; text-align: left; padding: 5px 6px; font-size: 9px; text-transform: uppercase; }
        table.lineas td { border-bottom: 1px solid #e2e8f0; padding: 4px 6px; }
        .num { text-align: right; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .falta { color: #b91c1c; font-weight: bold; }
        .sobra { color: #15803d; font-weight: bold; }
        .pie { margin-top: 14px; color: #64748b; font-size: 8px; }
    </style>
</head>
<body>
    <h1>Reporte de diferencias · Aforo AFO-<?php echo e($aforo->id); ?></h1>
    <div class="sub">BL <?php echo e($aforo->bl->numero_bl); ?> · <?php echo e($aforo->bl->cliente->codigo_corto); ?> · <?php echo e($aforo->bl->cliente->razon_social); ?></div>

    <table class="resumen">
        <tr>
            <td>Líneas: <strong><?php echo e($totales['lineas']); ?></strong></td>
            <td>Con diferencia: <strong><?php echo e($totales['con_diferencia']); ?></strong></td>
            <td>Faltantes: <strong><?php echo e($totales['faltantes']); ?></strong></td>
            <td>Sobrantes: <strong><?php echo e($totales['sobrantes']); ?></strong></td>
            <td>Seriales: <strong><?php echo e($totales['seriales']); ?></strong></td>
        </tr>
    </table>

    <table class="lineas">
        <thead>
            <tr>
                <th>Pallet</th><th>Bodega</th><th>Bulto</th><th>SKU</th><th>Descripción</th><th>Unidad</th>
                <th class="num">Manifiesto</th><th class="num">Contada</th><th class="num">Diferencia</th><th class="num">Seriales</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $filas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="mono"><?php echo e($f['pallet']); ?></td>
                    <td class="mono"><?php echo e($f['bodega']); ?></td>
                    <td class="mono"><?php echo e($f['bulto']); ?></td>
                    <td class="mono"><?php echo e($f['sku']); ?></td>
                    <td><?php echo e($f['descripcion']); ?></td>
                    <td><?php echo e($f['unidad']); ?></td>
                    <td class="num"><?php echo e($f['manifiesto']); ?></td>
                    <td class="num"><?php echo e($f['contada']); ?></td>
                    <td class="<?php echo \Illuminate\Support\Arr::toCssClasses(['num', 'falta' => $f['signo'] < 0, 'sobra' => $f['signo'] > 0]); ?>"><?php echo e($f['signo'] > 0 ? '+' : ''); ?><?php echo e($f['diferencia']); ?></td>
                    <td class="num"><?php echo e($f['seriales'] ?: ''); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <div class="pie">Generado el <?php echo e($generado); ?> · Kosmos WMS · Las diferencias se aplicaron con el movimiento AFORO (referencia AFO-<?php echo e($aforo->id); ?>).</div>
</body>
</html>
<?php /**PATH E:\Kosmos\resources\views/reportes/aforo-diferencias.blade.php ENDPATH**/ ?>
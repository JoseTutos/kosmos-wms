
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 6pt 9pt; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #000; margin: 0; }
        .titulo { font-size: 9pt; font-weight: bold; letter-spacing: 1pt; }
        .barras { margin: 3pt 0 1pt; text-align: center; }
        .barras img { width: 250pt; height: 46pt; }
        .codigo { text-align: center; font-family: DejaVu Sans Mono, monospace; font-size: 10pt; font-weight: bold; }
        .linea { margin-top: 1pt; white-space: nowrap; overflow: hidden; }
    </style>
</head>
<body>
    <div class="titulo"><?php echo e($titulo); ?></div>
    <div class="barras"><img src="data:image/svg+xml;base64,<?php echo e($barrasSvg); ?>" alt="<?php echo e($barras); ?>"></div>
    <div class="codigo"><?php echo e($barras); ?></div>
    <?php $__currentLoopData = array_slice($lineas, 0, 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $linea): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="linea"><?php echo e($linea); ?></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</body>
</html>
<?php /**PATH E:\Kosmos\resources\views/etiquetas/pdf.blade.php ENDPATH**/ ?>
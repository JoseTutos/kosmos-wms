
<?php
    $conPanel = (bool) config('kosmos.login.panel', true);
    $fondo    = config('kosmos.login.fondo'); // ruta relativa a public/, o null
?>
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $__env->yieldContent('titulo', 'Ingresar'); ?> · Kosmos WMS</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="min-h-screen bg-white font-sans text-slate-800 antialiased">
<div class="flex min-h-screen">

    
    <?php if($conPanel): ?>
        <aside class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-marca-900 bg-cover bg-center p-10 text-white lg:flex"
               <?php if($fondo): ?> style="background-image: url('<?php echo e(asset($fondo)); ?>')" <?php endif; ?>>
            <?php if($fondo): ?>
                <div class="absolute inset-0 bg-marca-900/70" aria-hidden="true"></div>
            <?php endif; ?>

            <div class="relative flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-[10px] bg-acento-600 text-xl font-bold">K</span>
                <span class="text-lg font-semibold tracking-tight">Kosmos WMS</span>
            </div>

            <div class="relative max-w-md">
                <p class="text-3xl font-semibold leading-tight tracking-tight"><?php echo $__env->yieldContent('lema', 'Control de bodega para Zona Franca'); ?></p>
                <p class="mt-3 text-[15px] leading-relaxed text-slate-300"><?php echo $__env->yieldContent('sublema', 'Recepción, legalización y despacho con trazabilidad completa.'); ?></p>
            </div>
        </aside>
    <?php endif; ?>

    <main class="flex flex-1 items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-sm">
            
            <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['mb-8 flex items-center gap-3', 'lg:hidden' => $conPanel]); ?>">
                <span class="flex size-10 items-center justify-center rounded-[10px] bg-marca-900 text-xl font-bold text-acento-400">K</span>
                <span class="text-lg font-semibold tracking-tight">Kosmos WMS</span>
            </div>

            <?php echo $__env->yieldContent('contenido'); ?>
        </div>
    </main>
</div>
</body>
</html>
<?php /**PATH E:\Kosmos\resources\views/layouts/guest.blade.php ENDPATH**/ ?>
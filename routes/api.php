<?php

/**
 * Kosmos WMS · Rutas API REST (/api/v1, token Sanctum) — An. §3.1: entrada de la app Flutter.
 *
 * Laravel antepone /api a todo este archivo; aquí se agrega /v1 (versión fijada en el Análisis).
 *
 * Grupos (SEG01, CUS-004, RS-19):
 *   1. Público                       → login (throttle contra fuerza bruta).
 *   2. auth:sanctum + cuenta.activa  → me, cambio de clave y logout (aunque la clave sea temporal).
 *   3. + password.changed + role     → endpoints operativos de OPERARIO y SUPERVISOR.
 *
 * Todas las respuestas son JSON, incluidos los errores (bootstrap/app.php → shouldRenderJsonWhen).
 */

use App\Http\Controllers\Api\V1\AforoController as ApiAforoController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BultoController;
use App\Http\Controllers\Api\V1\EscaneoController;
use App\Http\Controllers\Api\V1\EtiquetaController;
use App\Http\Controllers\Api\V1\PalletController;
use App\Http\Controllers\Api\V1\RecepcionController;
use App\Http\Controllers\Api\V1\UbicarController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {

    // 1. Público (sin idempotencia: guardar la respuesta guardaría el token en texto plano)
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');

    // 2. Token válido, cuenta activa y rol de la app (solo OPERARIO, Plan §2.1)
    Route::middleware(['auth:sanctum', 'cuenta.activa', 'role:OPERARIO', 'throttle:240,1'])->group(function () {

        // Disponibles aunque la clave sea temporal (si no, el operario nunca podría cambiarla)
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::put('auth/password', [AuthController::class, 'password'])->middleware('idempotencia')->name('auth.password');

        // 3. Operación móvil: clave ya cambiada + sin duplicados (D13)
        Route::middleware(['password.changed', 'idempotencia'])->group(function () {
            // CUS-006 Recepción (R, D3–D8)
            Route::get('recepciones', [RecepcionController::class, 'index'])->name('recepciones.index');
            Route::get('recepciones/{bl}', [RecepcionController::class, 'show'])->name('recepciones.show');
            Route::post('recepciones/{bl}/iniciar', [RecepcionController::class, 'iniciar'])->name('recepciones.iniciar');
            Route::post('recepciones/{bl}/bultos', [BultoController::class, 'store'])->name('bultos.store');
            Route::post('recepciones/{bl}/finalizar', [RecepcionController::class, 'finalizar'])->name('recepciones.finalizar');
            Route::get('bultos/{bulto:codigo}', [BultoController::class, 'show'])->name('bultos.show');
            Route::post('bultos/{bulto:codigo}/anular', [BultoController::class, 'anular'])->name('bultos.anular');

            // CUS-006 Pallets por escaneo (P1–P4)
            Route::post('recepciones/{bl}/pallets', [PalletController::class, 'store'])->name('pallets.store');
            Route::get('pallets/{pallet:codigo}', [PalletController::class, 'show'])->name('pallets.show');
            Route::post('pallets/{pallet:codigo}/bultos', [PalletController::class, 'agregar'])->name('pallets.agregar');
            Route::post('pallets/{pallet:codigo}/retirar', [PalletController::class, 'retirar'])->name('pallets.retirar');
            Route::post('pallets/{pallet:codigo}/cerrar', [PalletController::class, 'cerrar'])->name('pallets.cerrar');
            Route::post('pallets/{pallet:codigo}/reabrir', [PalletController::class, 'reabrir'])->name('pallets.reabrir');
            Route::delete('pallets/{pallet:codigo}', [PalletController::class, 'destroy'])->name('pallets.destroy');

            // CUS-007 Reimpresión en la app (E4, E5, E7)
            Route::post('etiquetas/reimprimir', [EtiquetaController::class, 'reimprimir'])->name('etiquetas.reimprimir');

            // CUS-008 Ubicar y trasladar + D14 escaneo
            Route::get('almacenamiento/destinos', [UbicarController::class, 'destinos'])->name('almacenamiento.destinos');
            Route::post('almacenamiento/ubicar', [UbicarController::class, 'ubicar'])->name('almacenamiento.ubicar');
            Route::get('escaneos/{codigo}', [EscaneoController::class, 'show'])->where('codigo', '.*')->name('escaneos.show');

            // CUS-009 Aforo (app): conteo por línea y seriales opcionales (A1–A9)
            Route::get('aforos', [ApiAforoController::class, 'index'])->name('aforos.index');
            Route::get('aforos/{aforo}', [ApiAforoController::class, 'show'])->name('aforos.show');
            Route::post('aforos/{aforo}/conteos', [ApiAforoController::class, 'conteos'])->name('aforos.conteos');
            Route::post('aforos/{aforo}/seriales', [ApiAforoController::class, 'seriales'])->name('aforos.seriales');

            // CUS-014 Picking:    Route::post('picking/{orden}/escanear', ...);
        });
    });
});

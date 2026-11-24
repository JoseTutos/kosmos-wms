<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST de la app Flutter del OPERARIO — /api/v1
|--------------------------------------------------------------------------
| bootstrap/app.php ya antepone "api/" y el grupo de middleware "api".
| Autenticación: Bearer token de Sanctum (sin sesión ni cookies).
| Todo POST de operación lleva Idempotency-Key (D13).
| Errores: { mensaje, codigo, errores?, datos? } (App\Enums\CodigoError).
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    /* ---------- 1. Público ---------- */
    // Sin idempotencia: guardar la respuesta guardaría el token en texto plano.
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login-api')
        ->name('auth.login');

    /* ---------- 2. Autenticado, ACTIVO y OPERARIO ---------- */
    Route::middleware(['auth:sanctum', 'cuenta.activa', 'role:OPERARIO', 'throttle:api'])->group(function () {

        // Disponibles aunque la clave sea temporal (si no, el operario nunca podría cambiarla).
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::put('auth/password', [AuthController::class, 'password'])
            ->middleware('idempotencia')
            ->name('auth.password');

        /* ---------- 3. Operación: exige haber cambiado la clave temporal ---------- */
        Route::middleware(['password.changed', 'idempotencia'])->group(function () {

            // Paso 4 · CUS-006/007 Recepción, pallets y etiquetas
            // Paso 5 · CUS-008 Ubicar y trasladar  ·  GET escaneos/{codigo} (D14)
            // Paso 6 · CUS-009 Aforo
        });
    });
});

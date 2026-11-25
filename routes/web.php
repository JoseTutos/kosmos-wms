<?php

/**
 * Kosmos WMS · Rutas WEB (Blade, sesión) — An. §3.1: MVC web para los roles administrativos.
 *
 * Orden de los grupos = orden de las reglas (SEG01, CUS-004, RS-19):
 *   1. guest                         → solo el login.
 *   2. auth + cuenta.activa          → usuario autenticado y ACTIVO (cierra sesiones de bloqueados).
 *      + cache.headers:no_store      → el navegador no guarda páginas privadas (botón "Atrás").
 *   3. password.changed              → lo operativo exige haber cambiado la clave temporal.
 *   4. role:...                      → cada módulo solo para sus roles (segregación RE-7).
 *
 * El OPERARIO no tiene web: AutenticacionService le niega el login por este canal.
 * Los módulos futuros se agregan dentro del grupo 3, cada uno con su middleware role.
 */

use App\Http\Controllers\Web\AforoController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\BlController;
use App\Http\Controllers\Web\BodegaController;
use App\Http\Controllers\Web\ClienteController;
use App\Http\Controllers\Web\EtiquetaController;
use App\Http\Controllers\Web\EtiquetaInicialController;
use App\Http\Controllers\Web\PerfilController;
use App\Http\Controllers\Web\ProductoController;
use App\Http\Controllers\Web\UbicacionController;
use App\Http\Controllers\Web\UsuarioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\LegalizacionController;
use App\Http\Controllers\Web\AjusteController;
use App\Http\Controllers\Web\EstadoLegalController;
use App\Http\Controllers\Web\HistoricoLegalizacionController;

/* ---------------------------------------------------------------
 | 1. Invitados
 |   throttle:10,1 → máx. 10 intentos por minuto por IP: frena la fuerza
 |   bruta y el abuso del bloqueo permanente (CUS-004) contra cuentas ajenas.
 * --------------------------------------------------------------- */
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
});

/* ---------------------------------------------------------------
 | 2. Autenticados y activos
 * --------------------------------------------------------------- */
Route::middleware(['auth', 'cuenta.activa', 'cache.headers:private;no_store'])->group(function () {

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Disponible aunque la clave sea temporal (si no, nadie podría cambiarla)
    Route::get('perfil/password', [PerfilController::class, 'editPassword'])->name('perfil.password.edit');
    Route::put('perfil/password', [PerfilController::class, 'updatePassword'])->name('perfil.password.update');

    /* -----------------------------------------------------------
     | 3. Operación: exige haber cambiado la clave temporal
     * ----------------------------------------------------------- */
    Route::middleware('password.changed')->group(function () {

        Route::view('/', 'dashboard')->name('dashboard');

        // ===== Módulo 1 · Seguridad — CUS-004 Administrar usuarios y roles =====
        Route::middleware('role:ADMIN_SISTEMA')->group(function () {
            Route::resource('usuarios', UsuarioController::class)->except(['show']); // destroy: solo bloqueados sin operaciones
            Route::patch('usuarios/{usuario}/bloquear', [UsuarioController::class, 'bloquear'])->name('usuarios.bloquear');
            Route::patch('usuarios/{usuario}/inactivar', [UsuarioController::class, 'inactivar'])->name('usuarios.inactivar');
            Route::patch('usuarios/{usuario}/activar', [UsuarioController::class, 'activar'])->name('usuarios.activar');
            Route::patch('usuarios/{usuario}/reset-password', [UsuarioController::class, 'resetPassword'])->name('usuarios.reset-password');
        });

        // ===== Módulo 1 · Maestros — CUS-001 a 003 (segregación RE-7: solo ADMIN_MAESTROS) =====
        // Sin destroy en ningún maestro: se inactivan (RS-04).
        Route::middleware('role:ADMIN_MAESTROS')->group(function () {

            // CUS-001 Clientes
            Route::resource('clientes', ClienteController::class)->except(['show', 'destroy']);
            Route::patch('clientes/{cliente}/inactivar', [ClienteController::class, 'inactivar'])->name('clientes.inactivar');
            Route::patch('clientes/{cliente}/activar', [ClienteController::class, 'activar'])->name('clientes.activar');

            // CUS-002 Productos (las rutas de importación van ANTES del resource)
            Route::get('productos/importar', [ProductoController::class, 'importarForm'])->name('productos.importar');
            Route::post('productos/importar', [ProductoController::class, 'importar'])->middleware('throttle:6,1')->name('productos.importar.store');
            Route::get('productos/plantilla', [ProductoController::class, 'plantilla'])->name('productos.plantilla');
            Route::resource('productos', ProductoController::class)->except(['show', 'destroy']);
            Route::patch('productos/{producto}/inactivar', [ProductoController::class, 'inactivar'])->name('productos.inactivar');
            Route::patch('productos/{producto}/activar', [ProductoController::class, 'activar'])->name('productos.activar');

            // CUS-003 Bodegas y ubicaciones internas (RS-17)
            Route::resource('bodegas', BodegaController::class)->except(['destroy']);
            Route::patch('bodegas/{bodega}/inactivar', [BodegaController::class, 'inactivar'])->name('bodegas.inactivar');
            Route::patch('bodegas/{bodega}/activar', [BodegaController::class, 'activar'])->name('bodegas.activar');
            Route::post('bodegas/{bodega}/ubicaciones', [UbicacionController::class, 'store'])->name('bodegas.ubicaciones.store');
            Route::patch('ubicaciones/{ubicacion}/inactivar', [UbicacionController::class, 'inactivar'])->name('ubicaciones.inactivar');
            Route::patch('ubicaciones/{ubicacion}/activar', [UbicacionController::class, 'activar'])->name('ubicaciones.activar');
        });

        // ===== Módulo 2 · Entrada — CUS-005 BL (B1: solo SUPERVISOR) =====
        Route::middleware('role:SUPERVISOR')->group(function () {
            Route::resource('bls', BlController::class);                                    // destroy: solo PROGRAMADO y sin bultos (B2)
            Route::patch('bls/{bl}/preinspeccion', [BlController::class, 'preinspeccion'])->name('bls.preinspeccion');
            Route::get('bls/{bl}/acta', [BlController::class, 'acta'])->name('bls.acta');  // descarga privada

            // CUS-009 Aforo: activar, seguir, cerrar o cancelar y descargar el reporte (D1, D2, A1–A9)
            Route::get('aforos', [AforoController::class, 'index'])->name('aforos.index');
            Route::get('aforos/create', [AforoController::class, 'create'])->name('aforos.create');
            Route::post('aforos', [AforoController::class, 'store'])->name('aforos.store');
            Route::get('aforos/{aforo}', [AforoController::class, 'show'])->name('aforos.show');
            Route::patch('aforos/{aforo}/cerrar', [AforoController::class, 'cerrar'])->name('aforos.cerrar');
            Route::patch('aforos/{aforo}/cancelar', [AforoController::class, 'cancelar'])->name('aforos.cancelar');
            Route::get('aforos/{aforo}/reporte/{formato}', [AforoController::class, 'reporte'])
                ->whereIn('formato', ['pdf', 'xlsx'])->name('aforos.reporte');
        });

        // ===== CUS-007 Reimpresión web en PDF (E6: Supervisor todas; Admin. de Maestros bodega y ubicación) =====
        Route::middleware('role:SUPERVISOR,ADMIN_MAESTROS')->group(function () {
            Route::get('etiquetas', [EtiquetaController::class, 'index'])->name('etiquetas.index');
            Route::post('etiquetas/reimprimir', [EtiquetaController::class, 'reimprimir'])->name('etiquetas.reimprimir');
            Route::post('etiquetas/imprimir', EtiquetaInicialController::class)->name('etiquetas.imprimir');
        });

        // ===== Módulo 3 · Control y legalización — CUS-010, 011, 012, 019 =====
        // CUS-010 Legalización (L1: ANALISTA y SUPERVISOR). Sin update ni destroy: la LEG es inmutable (L18).
        Route::middleware('role:ANALISTA,SUPERVISOR')->group(function () {
            Route::get('legalizaciones', [LegalizacionController::class, 'index'])->name('legalizaciones.index');
            Route::get('legalizaciones/create', [LegalizacionController::class, 'create'])->name('legalizaciones.create');
            Route::get('legalizaciones/autorizacion', [LegalizacionController::class, 'autorizacion'])->name('legalizaciones.autorizacion'); // L12
            Route::post('legalizaciones', [LegalizacionController::class, 'store'])->name('legalizaciones.store');
            Route::get('legalizaciones/{legalizacion}', [LegalizacionController::class, 'show'])->name('legalizaciones.show');
            Route::get('legalizaciones/{legalizacion}/soporte', [LegalizacionController::class, 'soporte'])->name('legalizaciones.soporte'); // descarga privada
        });

        // CUS-019 Ajustes de inventario (J1, RS-54: solo SUPERVISOR). Sin update ni destroy: solo inserción.
        Route::middleware('role:SUPERVISOR')->group(function () {
            Route::get('ajustes', [AjusteController::class, 'index'])->name('ajustes.index');
            Route::get('ajustes/create', [AjusteController::class, 'create'])->name('ajustes.create');
            Route::post('ajustes', [AjusteController::class, 'store'])->name('ajustes.store');
            Route::get('ajustes/{ajuste}', [AjusteController::class, 'show'])->name('ajustes.show');
            Route::get('ajustes/{ajuste}/soporte', [AjusteController::class, 'soporte'])->name('ajustes.soporte'); // descarga privada
        });

        // CUS-011 Estado legal y CUS-012 Históricos (C4, H1–H3): solo lectura. El OPERARIO no tiene acceso (RS-53).
        Route::middleware('role:SUPERVISOR,ANALISTA,GERENTE')->group(function () {
            Route::get('estado-legal', [EstadoLegalController::class, 'index'])->name('estado-legal.index');
            Route::get('estado-legal/{bl}', [EstadoLegalController::class, 'show'])->name('estado-legal.show');

            Route::get('historicos/legalizaciones', [HistoricoLegalizacionController::class, 'index'])->name('historicos.legalizaciones.index');
            Route::get('historicos/legalizaciones/exportar/{formato}', [HistoricoLegalizacionController::class, 'exportar'])
                ->whereIn('formato', ['xlsx', 'csv'])->name('historicos.legalizaciones.exportar');
            Route::get('historicos/legalizaciones/{legalizacion}', [HistoricoLegalizacionController::class, 'show'])->name('historicos.legalizaciones.show');
            Route::get('historicos/legalizaciones/{legalizacion}/soporte', [HistoricoLegalizacionController::class, 'soporte'])->name('historicos.legalizaciones.soporte');
        });
        // ===== Módulo 5 · Consultas y auditoría — CUS-015 a 018 =====
        // Route::middleware('role:ANALISTA,SUPERVISOR,GERENTE')->group(function () { ... });
        // Route::middleware('role:ADMIN_SISTEMA,GERENTE')->group(function () { ... }); // bitácora
    });
});

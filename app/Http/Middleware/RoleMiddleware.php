<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kosmos WMS · Capa de Presentación (Middleware) · Transversal a todos los módulos
 *
 * Cumple:
 *   - RS-19: seis roles excluyentes; cada ruta declara qué roles la usan.
 *   - RE-7: segregación de funciones (p. ej., ADMIN_SISTEMA no entra a maestros).
 *   - SEG01: autorización por rol antes de llegar al controlador (An. §3.3.1).
 *
 * Uso en rutas:  ->middleware('role:ADMIN_SISTEMA')   o   'role:ANALISTA,SUPERVISOR'
 * Registro del alias 'role':
 *   - Laravel 11+: bootstrap/app.php → $middleware->alias([...])
 *   - Laravel 10:  app/Http/Kernel.php → $middlewareAliases
 *
 * Restricción: decide QUIÉN entra. En QUÉ ESTADO se puede operar un documento
 * (p. ej., BL editable solo en PROGRAMADO) lo valida el modelo o el servicio.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Rol::from falla de inmediato si una ruta tiene un rol mal escrito (error de programación, no de usuario).
        $permitidos = array_map(fn (string $r) => Rol::from($r), $roles);

        abort_unless($user?->tieneRol(...$permitidos), 403, 'Su rol no tiene permiso para esta acción.');

        return $next($request);
    }
}

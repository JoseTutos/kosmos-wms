<?php

namespace App\Http\Middleware;

use App\Enums\CodigoError;
use App\Support\ErrorApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige haber cambiado la clave temporal antes de operar.
 *   - API: 403 PASSWORD_TEMPORAL (la app abre la pantalla de cambio → PUT /api/v1/auth/password).
 *   - Web: redirige a perfil.password.edit.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->debe_cambiar_password) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return ErrorApi::respuesta(CodigoError::PASSWORD_TEMPORAL, estado: 403);
        }

        return redirect()->route('perfil.password.edit');
    }
}

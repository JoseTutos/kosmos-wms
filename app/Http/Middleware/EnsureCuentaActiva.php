<?php

namespace App\Http\Middleware;

use App\Enums\CodigoError;
use App\Support\ErrorApi;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta la sesión de una cuenta que dejó de estar ACTIVA (inactivada o bloqueada mientras estaba dentro).
 *   - API: 401 CUENTA_NO_ACTIVA y revoca sus tokens.
 *   - Web: cierra la sesión y vuelve al login con el mensaje.
 */
class EnsureCuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->estaActivo()) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            $user->tokens()->delete(); // normalmente ya los revocó quien la inactivó; esto cubre cambios directos en BD

            return ErrorApi::respuesta(CodigoError::CUENTA_NO_ACTIVA, estado: 401);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => CodigoError::CUENTA_NO_ACTIVA->mensaje()]);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * S1: además de los 120 min de inactividad (SESSION_LIFETIME), la sesión web vence a las 12 h
 * de iniciada (session.absolute_lifetime), igual que el token de la app.
 * El inicio se marca en la primera petición autenticada y va ligado al usuario.
 * S2: redirect()->guest() guarda la URL para que, al volver a entrar, el usuario regrese a la misma pantalla.
 */
final class SesionAbsoluta
{
    public const CLAVE = 'kosmos.sesion_inicio';

    public const MENSAJE = 'Su sesión superó el máximo de 12 horas. Inicie sesión de nuevo: su trabajo en curso se conservó en este navegador.';

    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('web');

        if (! $request->hasSession() || ! $guard->check()) {
            return $next($request);
        }

        $sesion = $request->session();
        $marca = $sesion->get(self::CLAVE);
        $usuarioId = (int) $guard->id();

        if (! is_array($marca) || (int) ($marca['usuario'] ?? 0) !== $usuarioId) {
            $sesion->put(self::CLAVE, ['usuario' => $usuarioId, 'desde' => now()->getTimestamp()]);

            return $next($request);
        }

        $maximo = (int) config('session.absolute_lifetime', 720) * 60;

        if ($maximo <= 0 || now()->getTimestamp() - (int) $marca['desde'] < $maximo) {
            return $next($request);
        }

        $guard->logout();
        $sesion->invalidate();
        $sesion->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => self::MENSAJE], 401);
        }

        return redirect()->guest(route('login'))->withErrors(['email' => self::MENSAJE]);
    }
}

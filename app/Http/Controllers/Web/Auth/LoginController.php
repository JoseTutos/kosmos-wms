<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Seguridad\AutenticacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Kosmos WMS · Capa de Presentación (Controlador Web) · Inicio de sesión
 *
 * Cumple:
 *   - SEG01: autenticación por sesión para los roles administrativos (An. §3.1, MVC web).
 *   - CUS-004: delega en AutenticacionService el bloqueo a los 5 intentos y el control por rol.
 *
 * Controlador delgado (An. §3.3): traduce HTTP ↔ servicio. No contiene reglas de negocio.
 *
 * Restricciones de seguridad:
 *   - Regenera la sesión al entrar (evita la fijación de sesión).
 *   - Al salir invalida la sesión y el token CSRF.
 *   - Con contraseña temporal solo deja ir al cambio de clave.
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AutenticacionService $auth): RedirectResponse
    {
        $user = $auth->autenticar($request->email, $request->password, 'web');

        Auth::login($user);
        $request->session()->regenerate();

        return $user->debe_cambiar_password
            ? redirect()->route('perfil.password.edit')
            : redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

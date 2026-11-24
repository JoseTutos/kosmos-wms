<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CambiarPasswordRequest;
use App\Services\Seguridad\AutenticacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Kosmos WMS · Capa de Presentación (Controlador Web) · Cambio de contraseña propio
 *
 * Cumple:
 *   - Decisión de diseño (CUS-004): el usuario cambia la clave temporal en su primer
 *     ingreso y puede volver a cambiarla cuando quiera.
 *
 * Por qué está separado de UsuarioController:
 *   UsuarioController es solo para ADMIN_SISTEMA (RE-7). Este lo usa cualquier
 *   usuario web sobre SU PROPIA cuenta.
 *
 * Restricción: la sesión se regenera tras el cambio de credenciales.
 */
class PerfilController extends Controller
{
    public function editPassword(): View
    {
        return view('perfil.password');
    }

    public function updatePassword(CambiarPasswordRequest $request, AutenticacionService $auth): RedirectResponse
    {
        $auth->cambiarPassword($request->user(), $request->password_actual, $request->password);

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('ok', 'Contraseña actualizada.');
    }
}

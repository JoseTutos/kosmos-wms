<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · Cambio de contraseña (web y API)
 *
 * Cumple:
 *   - Decisión de diseño (CUS-004): el usuario define su propia clave tras la temporal.
 *   - Seguridad de credenciales del SRS (bcrypt): política mínima de complejidad.
 *
 * Restricciones:
 *   - Mínimo 8 caracteres, con letras y números, confirmada y distinta de la actual.
 *   - Máximo 72: bcrypt solo usa los primeros 72 bytes; una clave más larga daría
 *     una falsa sensación de seguridad.
 *   - Que la clave actual sea correcta lo verifica AutenticacionService, porque la regla
 *     'current_password' depende del guard (sesión web vs. token Sanctum).
 */
class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta ya exige usuario autenticado
    }

    public function rules(): array
    {
        return [
            'password_actual' => ['required', 'string', 'max:255'],
            'password'        => ['required', 'string', 'max:72', 'confirmed', 'different:password_actual',
                                  Password::min(8)->letters()->numbers()],
        ];
    }

    public function attributes(): array
    {
        return ['password_actual' => 'contraseña actual', 'password' => 'nueva contraseña'];
    }
}

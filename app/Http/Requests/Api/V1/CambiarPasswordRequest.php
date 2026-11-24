<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Misma política que el cambio de clave web (PerfilController::updatePassword).
 * Si las reglas web son distintas, copie aquí exactamente las de esa FormRequest:
 * la política de contraseñas debe ser una sola para los dos canales.
 */
class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_actual' => ['required', 'string'],
            'password'        => ['required', 'string', 'confirmed', 'different:password_actual', Password::min(8)->letters()->numbers()],
        ];
    }

    public function attributes(): array
    {
        return ['password_actual' => 'contraseña actual', 'password' => 'nueva contraseña'];
    }
}

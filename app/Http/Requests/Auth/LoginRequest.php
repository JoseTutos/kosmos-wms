<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · Login web y API
 *
 * Cumple:
 *   - An. §3.1: la validación de formato va en el FormRequest, no en el controlador.
 *   - SEG01: entrada al proceso de autenticación (las reglas de negocio están en AutenticacionService).
 *
 * Restricciones:
 *   - El correo se normaliza a minúsculas: "Juan@x.com" y "juan@x.com" son el mismo usuario.
 *   - Longitudes máximas para no procesar cargas desmedidas.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ruta pública: aún no hay usuario
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->email))]);
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'email', 'max:150'],
            'password'    => ['required', 'string', 'max:255'],
            'dispositivo' => ['nullable', 'string', 'max:100'], // solo API: nombre del token Sanctum
        ];
    }
}

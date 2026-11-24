<?php

namespace App\Http\Requests\Usuario;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · CUS-004 Alta de usuario
 *
 * Cumple:
 *   - CUS-004 / RS-19: un solo rol por usuario, uno de los 6 valores del enum Rol
 *     (los mismos del CHECK de users.rol).
 *   - Restricción de BD: users.email UNIQUE (se valida antes para dar un mensaje claro).
 *
 * Restricciones:
 *   - NO recibe contraseña ni estado: el sistema genera la clave temporal y el
 *     usuario nace ACTIVO. Así el formulario no puede saltarse esas reglas.
 *   - El correo se guarda en minúsculas (coherente con LoginRequest).
 *   - La autorización (solo ADMIN_SISTEMA) la hace el middleware role de la ruta.
 */
class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->email))]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'email'  => ['required', 'email', 'max:150', 'unique:users,email'],
            'rol'    => ['required', new Enum(Rol::class)],
        ];
    }
}

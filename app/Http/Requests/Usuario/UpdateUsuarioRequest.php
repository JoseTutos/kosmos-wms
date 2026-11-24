<?php

namespace App\Http\Requests\Usuario;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · CUS-004 Edición de usuario
 *
 * Cumple:
 *   - CUS-004 / RS-19: cambio de rol limitado a los 6 valores del enum Rol.
 *   - Restricción de BD: users.email UNIQUE, ignorando al propio usuario.
 *
 * Restricciones:
 *   - Estado y contraseña NO se editan aquí: tienen acciones propias (bloquear,
 *     inactivar, activar, resetPassword), cada una con su control y su registro
 *     en la bitácora. Así nadie desbloquea "a escondidas" desde el formulario.
 *   - Las protecciones del admin (no cambiarse su propio rol, último admin activo)
 *     las aplica UsuarioController@update.
 */
class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // solo ADMIN_SISTEMA, vía middleware role
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->email))]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'email'  => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->route('usuario'))],
            'rol'    => ['required', new Enum(Rol::class)],
        ];
    }
}

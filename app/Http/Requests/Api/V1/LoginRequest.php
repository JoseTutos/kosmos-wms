<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'       => Str::lower(trim((string) $this->input('email'))),
            'dispositivo' => trim((string) $this->input('dispositivo')),
        ]);
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'string', 'email', 'max:255'],
            'password'    => ['required', 'string', 'max:255'],
            // Nombre del equipo (p. ej. "Zebra TC21 · Muelle 2"). Un token por dispositivo.
            'dispositivo' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'correo', 'password' => 'contraseña', 'dispositivo' => 'dispositivo'];
    }
}

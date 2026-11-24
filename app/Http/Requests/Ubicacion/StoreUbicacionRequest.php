<?php

namespace App\Http\Requests\Ubicacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · CUS-003 Nueva ubicación interna
 *
 * Cumple RS-17: el código es único DENTRO de su bodega (UNIQUE (bodega_id, codigo) en la BD).
 * Que la bodega maneje ubicaciones lo valida el modelo Ubicacion al crear.
 */
class StoreUbicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['codigo' => mb_strtoupper(trim((string) $this->codigo))]);
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'regex:/^[A-Z0-9-]{1,20}$/',
                         Rule::unique('ubicaciones', 'codigo')->where('bodega_id', $this->route('bodega')->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.regex'  => 'El código admite hasta 20 letras, números o guiones (p. ej., A-01).',
            'codigo.unique' => 'Esa ubicación ya existe en esta bodega.',
        ];
    }

    public function attributes(): array
    {
        return ['codigo' => 'código de ubicación'];
    }
}

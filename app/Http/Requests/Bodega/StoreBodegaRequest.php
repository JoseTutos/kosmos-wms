<?php

namespace App\Http\Requests\Bodega;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · CUS-003 Alta de bodega
 *
 * Cumple:
 *   - CUS-003: código único (UNIQUE en la BD), tamaño en m² mayor que 0 (CHECK tamano_m2 > 0),
 *     ubicaciones internas opcionales (RS-17).
 *   - Largos y decimales = migración: código 20, nombre 100, tamaño NUMERIC(10,2).
 *   - Código: letras, números y guion, en mayúsculas (va impreso en la etiqueta).
 *
 * m2_ocupados y estado NO se reciben: la ocupación la calcula el sistema y el estado
 * tiene sus acciones.
 */
class StoreBodegaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ADMIN_MAESTROS, vía middleware role
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo'             => mb_strtoupper(trim((string) $this->codigo)),
            'tamano_m2'          => str_replace(',', '.', (string) $this->tamano_m2),
            'maneja_ubicaciones' => $this->boolean('maneja_ubicaciones'),
        ]);
    }

    public function rules(): array
    {
        return [
            'codigo'             => ['required', 'regex:/^[A-Z0-9-]{2,20}$/', 'unique:bodegas,codigo'],
            'nombre'             => ['required', 'string', 'max:100'],
            'tamano_m2'          => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999'],
            'maneja_ubicaciones' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.regex'  => 'El código admite de 2 a 20 letras, números o guiones (p. ej., BOD-A).',
            'codigo.unique' => 'Ya existe una bodega con ese código.',
            'tamano_m2.gt'  => 'El tamaño debe ser mayor que 0 m².',
        ];
    }

    public function attributes(): array
    {
        return ['codigo' => 'código', 'nombre' => 'nombre', 'tamano_m2' => 'tamaño (m²)', 'maneja_ubicaciones' => 'ubicaciones internas'];
    }
}

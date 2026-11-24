<?php

namespace App\Http\Requests\Bodega;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · CUS-003 Edición de bodega
 *
 * El código es INMUTABLE (va impreso en la etiqueta): no está en estas reglas, así que
 * validated() nunca lo incluye. El trait CamposInmutables del modelo es la segunda barrera.
 * Reducir el tamaño por debajo de lo ocupado se permite: la bodega se marca en rojo
 * (decisión de diseño: sobrecupo con alerta visual).
 */
class UpdateBodegaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tamano_m2'          => str_replace(',', '.', (string) $this->tamano_m2),
            'maneja_ubicaciones' => $this->boolean('maneja_ubicaciones'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre'             => ['required', 'string', 'max:100'],
            'tamano_m2'          => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999'],
            'maneja_ubicaciones' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['tamano_m2.gt' => 'El tamaño debe ser mayor que 0 m².'];
    }

    public function attributes(): array
    {
        return ['nombre' => 'nombre', 'tamano_m2' => 'tamaño (m²)', 'maneja_ubicaciones' => 'ubicaciones internas'];
    }
}

<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · CUS-001 Edición de cliente
 *
 * Cumple CUS-001: NIT y código corto son INMUTABLES. No están en estas reglas, así que
 * validated() nunca los incluye aunque alguien los envíe a mano. El trait
 * CamposInmutables del modelo es la segunda barrera.
 *
 * El estado no se edita aquí: tiene sus acciones (inactivar / activar).
 */
class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'razon_social'    => ['required', 'string', 'max:200'],
            'direccion'       => ['required', 'string', 'max:255'],
            'telefono'        => ['required', 'string', 'max:30', 'regex:/^[0-9+()\s-]{7,30}$/'],
            'contacto_nombre' => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return ['telefono.regex' => 'El teléfono solo admite números, espacios y los signos + ( ) -.'];
    }

    public function attributes(): array
    {
        return [
            'razon_social'    => 'razón social',
            'direccion'       => 'dirección',
            'telefono'        => 'teléfono',
            'contacto_nombre' => 'nombre de contacto',
        ];
    }
}

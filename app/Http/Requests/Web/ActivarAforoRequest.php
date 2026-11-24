<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

/** CUS-009 activar: el BL y los bultos UBICADOS elegidos (A1: todos por defecto en la pantalla). */
class ActivarAforoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // AforoPolicy::create en el controlador
    }

    public function rules(): array
    {
        return [
            'bl_id'    => ['required', 'integer', 'exists:bls,id'],
            'bultos'   => ['required', 'array', 'min:1', 'max:2000'],
            'bultos.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return ['bultos.required' => 'Elija al menos un bulto para el aforo.'];
    }

    public function attributes(): array
    {
        return ['bl_id' => 'BL', 'bultos' => 'bultos'];
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/** CUS-008: pallet escaneado + destino elegido + escaneo de la etiqueta del destino (RS-38). */
class UbicarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // la ruta ya exige role:OPERARIO
    }

    public function rules(): array
    {
        return [
            'pallet'     => ['required', 'string', 'max:30'],
            'bodega'     => ['required', 'string', 'max:30'],
            'ubicacion'  => ['nullable', 'string', 'max:30'],
            'escaneo'    => ['required', 'string', 'max:70'],
            'referencia' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return ['escaneo' => 'etiqueta del destino', 'referencia' => 'referencia de ubicación'];
    }
}

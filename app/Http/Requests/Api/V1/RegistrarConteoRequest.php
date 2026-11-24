<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/** POST /aforos/{aforo}/conteos { detalle_id, cantidad }. La app puede enviar coma decimal. */
class RegistrarConteoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // AforoPolicy::contar en el controlador
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['cantidad' => str_replace(',', '.', trim((string) $this->input('cantidad')))]);
    }

    public function rules(): array
    {
        return [
            'detalle_id' => ['required', 'integer'],
            'cantidad'   => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:99999999999'],
        ];
    }

    public function attributes(): array
    {
        return ['detalle_id' => 'línea del aforo', 'cantidad' => 'cantidad contada'];
    }
}

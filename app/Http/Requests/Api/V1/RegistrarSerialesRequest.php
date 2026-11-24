<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/** POST /aforos/{aforo}/seriales { detalle_id }: genera un serial por unidad contada (RS-44). */
class RegistrarSerialesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['detalle_id' => ['required', 'integer']];
    }

    public function attributes(): array
    {
        return ['detalle_id' => 'línea del aforo'];
    }
}

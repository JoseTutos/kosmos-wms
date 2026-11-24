<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/** POST /api/v1/recepciones/{bl}/finalizar { observacion? }. Si hay diferencias, el dominio la exige (RS-33). */
class FinalizarRecepcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['observacion' => ['nullable', 'string', 'max:1000']];
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/** POST /api/v1/pallets/{pallet}/bultos y /retirar { bulto: "BUL-DDMMAA-0000" } (código escaneado). */
class EscaneoBultoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['bulto' => strtoupper(trim((string) $this->input('bulto')))]);
    }

    public function rules(): array
    {
        return ['bulto' => ['required', 'string', 'max:16']];
    }
}

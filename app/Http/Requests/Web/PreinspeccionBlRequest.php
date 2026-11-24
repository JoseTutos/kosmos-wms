<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

/** B6 / RS-28: fecha y acta de preinspección (trámite externo, opcional). */
class PreinspeccionBlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('preinspeccion', $this->route('bl'));
    }

    public function rules(): array
    {
        return [
            'fecha_preinspeccion' => ['nullable', 'date', 'before_or_equal:today'],
            'acta'                => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'quitar_acta'         => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['fecha_preinspeccion' => 'fecha de preinspección', 'acta' => 'acta de preinspección'];
    }
}

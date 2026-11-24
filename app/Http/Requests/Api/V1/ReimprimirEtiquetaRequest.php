<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\MotivoReimpresion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/etiquetas/reimprimir { tipo: bulto|pallet, codigo, motivo?, detalle? }
 * El motivo solo puede faltar unos minutos después del alta (E7): lo decide EtiquetaService.
 */
class ReimprimirEtiquetaRequest extends FormRequest
{
    /** Tipos que este canal acepta (la web los amplía). */
    protected function tipos(): array
    {
        return ['bulto', 'pallet'];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['codigo' => strtoupper(trim((string) $this->input('codigo')))]);
    }

    public function rules(): array
    {
        return [
            'tipo'    => ['required', Rule::in($this->tipos())],
            'codigo'  => ['required', 'string', 'max:80'],
            'motivo'  => ['nullable', Rule::enum(MotivoReimpresion::class)],
            'detalle' => ['nullable', 'required_if:motivo,'.MotivoReimpresion::OTRO->value, 'string', 'max:200'],
        ];
    }

    public function attributes(): array
    {
        return ['tipo' => 'tipo de etiqueta', 'codigo' => 'código', 'motivo' => 'motivo', 'detalle' => 'detalle del motivo'];
    }

    public function motivo(): ?MotivoReimpresion
    {
        return MotivoReimpresion::tryFrom((string) $this->validated('motivo'));
    }
}

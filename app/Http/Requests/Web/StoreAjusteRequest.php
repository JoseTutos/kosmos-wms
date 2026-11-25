<?php

namespace App\Http\Requests\Web;

use App\Enums\EstadoLegal;
use App\Enums\MotivoAjuste;
use App\Enums\TipoAjuste;
use App\Models\Ajuste;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** CUS-019: formato del ajuste. Las reglas de cantidad (J2, J3, J10) las revalida AjusteService con la línea bloqueada. */
class StoreAjusteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Ajuste::class);
    }

    protected function prepareForValidation(): void
    {
        $cantidad = trim((string) $this->input('cantidad'));

        $this->merge([
            'cantidad'    => str_contains($cantidad, ',') ? str_replace(['.', ','], ['', '.'], $cantidad) : $cantidad,
            'descripcion' => trim((string) $this->input('descripcion')),
        ]);
    }

    public function rules(): array
    {
        return [
            'bulto_detalle_id'        => ['required', 'integer', Rule::exists('bulto_detalles', 'id')],
            'tipo'                    => ['required', Rule::enum(TipoAjuste::class)],
            'estado_afectado'         => ['required', Rule::enum(EstadoLegal::class)],
            'cantidad'                => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:99999999999'],
            'motivo'                  => ['required', Rule::enum(MotivoAjuste::class)],
            'descripcion'             => ['required', 'string', 'max:2000'],
            'movimiento_corregido_id' => ['nullable', 'required_if:motivo,'.MotivoAjuste::CORRECCION_REGISTRO->value, 'integer', Rule::exists('movimientos', 'id')],
            'soporte'                 => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'aforo_id'                => ['nullable', 'integer', Rule::exists('aforos', 'id')],
            'confirmacion'            => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'descripcion.required'                => 'Describa el motivo del ajuste (EX-03).',
            'movimiento_corregido_id.required_if' => 'Con el motivo Corrección de registro indique el movimiento que corrige (RS-57).',
            'soporte.mimes'                       => 'El soporte debe ser PDF, JPG o PNG.',
            'soporte.max'                         => 'El soporte no puede superar 10 MB.',
            'confirmacion.accepted'               => 'Confirme que entiende que el ajuste es irreversible.',
        ];
    }

    public function attributes(): array
    {
        return [
            'bulto_detalle_id'        => 'línea del bulto',
            'estado_afectado'         => 'estado afectado',
            'movimiento_corregido_id' => 'movimiento que corrige',
        ];
    }

    public function movimientoCorregidoId(): ?int
    {
        return $this->filled('movimiento_corregido_id') ? (int) $this->validated('movimiento_corregido_id') : null;
    }
}

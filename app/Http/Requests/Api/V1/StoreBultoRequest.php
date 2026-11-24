<?php

namespace App\Http\Requests\Api\V1;

use App\Services\Nucleo\ConfiguracionService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/recepciones/{bl}/bultos
 * { peso_bruto_kg, lineas: [{producto_id, cantidad}], repetir?: N, pallet?: "PLT-…" }
 * Formato aquí; R5 (UND entero), RS-25 (producto del BL) y el estado del BL en RecepcionService.
 */
class StoreBultoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // rol OPERARIO por la ruta
    }

    protected function prepareForValidation(): void
    {
        $lineas = collect($this->input('lineas', []))
            ->map(fn ($l) => is_array($l) ? ['producto_id' => $l['producto_id'] ?? null, 'cantidad' => $this->decimal($l['cantidad'] ?? '')] : $l)
            ->all();

        $this->merge([
            'peso_bruto_kg' => $this->decimal($this->input('peso_bruto_kg', '')),
            'lineas'        => $lineas,
            'repetir'       => $this->input('repetir', 1),
            'pallet'        => filled($this->input('pallet')) ? strtoupper(trim((string) $this->input('pallet'))) : null,
        ]);
    }

    public function rules(): array
    {
        $maximo = app(ConfiguracionService::class)->entero('repetir_max');

        return [
            'peso_bruto_kg'        => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],   // R4
            'lineas'               => ['required', 'array', 'min:1', 'max:50'],
            'lineas.*.producto_id' => ['required', 'integer', 'distinct'],                                // RS-29: una línea por producto
            'lineas.*.cantidad'    => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:99999999999'],
            'repetir'              => ['required', 'integer', 'min:1', "max:{$maximo}"],                 // R3 (configurable)
            'pallet'               => ['nullable', 'string', 'max:16'],
        ];
    }

    public function attributes(): array
    {
        return [
            'peso_bruto_kg'        => 'peso bruto',
            'lineas'               => 'productos del bulto',
            'lineas.*.producto_id' => 'producto de la línea :position',
            'lineas.*.cantidad'    => 'cantidad de la línea :position',
            'repetir'              => 'cantidad de bultos a repetir',
        ];
    }

    private function decimal(mixed $valor): string
    {
        $valor = trim((string) $valor);

        return str_contains($valor, ',') ? str_replace(['.', ','], ['', '.'], $valor) : $valor;
    }
}

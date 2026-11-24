<?php

namespace App\Http\Requests\Web;

use App\Models\Bl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** CUS-005: formato del BL y su manifiesto. Las reglas de negocio se repiten en BlService. */
class StoreBlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Bl::class);
    }

    /** Cliente dueño de los productos del manifiesto. En la edición sale del BL (no cambia). */
    protected function clienteId(): int
    {
        return $this->integer('cliente_id');
    }

    protected function prepareForValidation(): void
    {
        $detalles = collect($this->input('detalles', []))
            ->filter(fn ($d) => is_array($d) && filled($d['producto_id'] ?? null))   // filas vacías del formulario
            ->map(fn ($d) => [
                'producto_id'        => $d['producto_id'],
                'bultos_esperados'   => trim((string) ($d['bultos_esperados'] ?? '')),
                'unidades_esperadas' => $this->decimal($d['unidades_esperadas'] ?? ''),
                'peso_bruto_kg'      => $this->decimal($d['peso_bruto_kg'] ?? ''),
            ])
            ->values()
            ->all();

        $this->merge([
            // B4: mayúsculas y sin espacios
            'numero_bl'         => Str::upper(preg_replace('/\s+/', '', (string) $this->input('numero_bl'))),
            'factura_comercial' => trim((string) $this->input('factura_comercial')),
            'detalles'          => $detalles,
        ]);
    }

    public function rules(): array
    {
        return [
            'numero_bl'         => ['required', 'string', 'min:4', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9\-\/.]*$/', Rule::unique('bls', 'numero_bl')],
            'cliente_id'        => ['required', 'integer', Rule::exists('clientes', 'id')->where('estado', 'ACTIVO')],
            'fecha_ingreso'     => ['required', 'date'],
            'factura_comercial' => ['required', 'string', 'max:50'],

            'fecha_preinspeccion' => ['nullable', 'date', 'before_or_equal:today'],
            'acta'                => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            'detalles'                      => ['required', 'array', 'min:1', 'max:200'],
            'detalles.*.producto_id'        => ['required', 'integer', 'distinct',
                Rule::exists('productos', 'id')->where('cliente_id', $this->clienteId())->where('estado', 'ACTIVO')],
            'detalles.*.bultos_esperados'   => ['required', 'integer', 'min:1', 'max:99999'],
            'detalles.*.unidades_esperadas' => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:99999999999'],
            'detalles.*.peso_bruto_kg'      => ['required', 'numeric', 'decimal:0,3', 'gt:0', 'max:999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_bl.regex'                => 'El número de BL solo admite letras, números, guion, barra y punto (sin espacios).',
            'detalles.required'              => 'Agregue al menos un producto al manifiesto.',
            'detalles.*.producto_id.distinct' => 'El producto del renglón :position está repetido: use un solo renglón por producto.',
            'detalles.*.producto_id.exists'  => 'El producto del renglón :position no está activo o no es de este cliente.',
        ];
    }

    public function attributes(): array
    {
        return [
            'numero_bl'                     => 'número de BL',
            'cliente_id'                    => 'cliente',
            'fecha_ingreso'                 => 'fecha de ingreso',
            'factura_comercial'             => 'factura comercial',
            'fecha_preinspeccion'           => 'fecha de preinspección',
            'acta'                          => 'acta de preinspección',
            'detalles.*.producto_id'        => 'producto del renglón :position',
            'detalles.*.bultos_esperados'   => 'bultos del renglón :position',
            'detalles.*.unidades_esperadas' => 'unidades del renglón :position',
            'detalles.*.peso_bruto_kg'      => 'peso del renglón :position',
        ];
    }

    /** "1.234,5" o "1234,5" → "1234.5" (mismo criterio que la importación de productos). */
    private function decimal(mixed $valor): string
    {
        $valor = trim((string) $valor);

        return str_contains($valor, ',') ? str_replace(['.', ','], ['', '.'], $valor) : $valor;
    }
}

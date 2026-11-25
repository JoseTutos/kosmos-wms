<?php

namespace App\Http\Requests\Web;

use App\Enums\TipoDocumentoAutorizacion;
use App\Models\Legalizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CUS-010: formato del carrito y de la autorización. El carrito llega como JSON en "carrito" (L10)
 * y se valida como "items". Las reglas de negocio las revalida LegalizacionService (no confía en el carrito).
 */
class StoreLegalizacionRequest extends FormRequest
{
    public const MAX_ITEMS = 500;

    public function authorize(): bool
    {
        return $this->user()->can('create', Legalizacion::class);
    }

    protected function prepareForValidation(): void
    {
        $carrito = json_decode((string) $this->input('carrito', ''), true);

        $items = is_array($carrito) && array_is_list($carrito)
            ? array_map(fn ($item) => is_array($item) ? [
                'modo'     => strtoupper((string) ($item['modo'] ?? '')),
                'bulto_id' => $item['bulto_id'] ?? null,
                'linea_id' => $item['linea_id'] ?? null,
                'cantidad' => isset($item['cantidad']) ? $this->decimal($item['cantidad']) : null,
                'seriales' => isset($item['seriales']) && is_array($item['seriales']) ? array_values($item['seriales']) : null,
            ] : $item, $carrito)
            : null;

        $this->merge([
            'items'               => $items,
            'numero_autorizacion' => trim((string) $this->input('numero_autorizacion')),
        ]);
    }

    public function rules(): array
    {
        return [
            'bl_id'              => ['required', 'integer', Rule::exists('bls', 'id')],
            'items'              => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*'            => ['array'],
            'items.*.modo'       => ['required', Rule::in(['A', 'B', 'C'])],
            'items.*.bulto_id'   => ['required_if:items.*.modo,A', 'nullable', 'integer'],
            'items.*.linea_id'   => ['required_if:items.*.modo,B,C', 'nullable', 'integer'],
            'items.*.cantidad'   => ['required_if:items.*.modo,B', 'nullable', 'numeric', 'decimal:0,3', 'gt:0', 'max:99999999999'],
            'items.*.seriales'   => ['required_if:items.*.modo,C', 'nullable', 'array', 'min:1', 'max:5000'],
            'items.*.seriales.*' => ['integer', 'distinct'],

            'tipo_documento'      => ['required', Rule::enum(TipoDocumentoAutorizacion::class)],
            'numero_autorizacion' => ['required', 'string', 'max:60'],
            'fecha_autorizacion'  => ['required', 'date'],
            'soporte'             => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'   => 'Agregue al menos un bulto, una cantidad o un serial al carrito.',
            'items.array'      => 'El carrito no es válido: recargue la página.',
            'items.max'        => 'El carrito admite máximo '.self::MAX_ITEMS.' ítems: divida la legalización.',
            'soporte.required' => 'Adjunte el soporte de la autorización (PDF, JPG o PNG, máximo 10 MB).',
            'soporte.mimes'    => 'El soporte debe ser PDF, JPG o PNG.',
            'soporte.max'      => 'El soporte no puede superar 10 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_documento'      => 'tipo de documento',
            'numero_autorizacion' => 'número de autorización',
            'fecha_autorizacion'  => 'fecha de autorización',
            'soporte'             => 'soporte',
            'items.*.modo'        => 'modo del ítem :position del carrito',
            'items.*.bulto_id'    => 'bulto del ítem :position del carrito',
            'items.*.linea_id'    => 'línea del ítem :position del carrito',
            'items.*.cantidad'    => 'cantidad del ítem :position del carrito',
            'items.*.seriales'    => 'seriales del ítem :position del carrito',
            'items.*.seriales.*'  => 'serial del ítem :position del carrito',
        ];
    }

    /** @return list<array{modo: string, bulto_id?: int, linea_id?: int, cantidad?: string, seriales?: list<int>}> */
    public function items(): array
    {
        return array_map(fn (array $i) => match ($i['modo']) {
            'A' => ['modo' => 'A', 'bulto_id' => (int) $i['bulto_id']],
            'B' => ['modo' => 'B', 'linea_id' => (int) $i['linea_id'], 'cantidad' => (string) $i['cantidad']],
            'C' => ['modo' => 'C', 'linea_id' => (int) $i['linea_id'], 'seriales' => array_map('intval', $i['seriales'])],
        }, $this->validated('items'));
    }

    /** @return array{tipo_documento: string, numero_autorizacion: string, fecha_autorizacion: string} */
    public function autorizacion(): array
    {
        return $this->safe()->only(['tipo_documento', 'numero_autorizacion', 'fecha_autorizacion']);
    }

    /** "1.234,5" o "1234,5" → "1234.5" (mismo criterio que StoreBlRequest). */
    private function decimal(mixed $valor): string
    {
        $valor = trim((string) $valor);

        return str_contains($valor, ',') ? str_replace(['.', ','], ['', '.'], $valor) : $valor;
    }
}

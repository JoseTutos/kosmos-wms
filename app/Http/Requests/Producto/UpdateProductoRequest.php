<?php

namespace App\Http\Requests\Producto;

use App\Enums\TipoAlmacenamiento;
use App\Enums\UnidadMedida;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · CUS-002 Edición de producto
 *
 * Cumple CUS-002: SKU y cliente son INMUTABLES. No están en estas reglas, así que
 * validated() nunca los incluye. El trait CamposInmutables del modelo es la segunda barrera.
 * La unidad de medida se protege en el controlador si ya hay mercancía registrada.
 */
class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'peso_unitario_kg' => str_replace(',', '.', (string) $this->peso_unitario_kg),
            'stock_minimo'     => filled($this->stock_minimo) ? str_replace(',', '.', (string) $this->stock_minimo) : '0',
        ]);
    }

    public function rules(): array
    {
        $producto = $this->route('producto');

        return [
            'descripcion'         => ['required', 'string', 'max:255',
                                      Rule::unique('productos', 'descripcion')->where('cliente_id', $producto->cliente_id)->ignore($producto)],
            'tipo_almacenamiento' => ['required', new Enum(TipoAlmacenamiento::class)],
            'unidad_medida'       => ['required', new Enum(UnidadMedida::class)],
            'peso_unitario_kg'    => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999'],
            'stock_minimo'        => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:99999999999'],
        ];
    }

    public function messages(): array
    {
        return ['descripcion.unique' => 'Este cliente ya tiene otro producto con esa descripción.'];
    }

    public function attributes(): array
    {
        return [
            'descripcion'         => 'descripción',
            'tipo_almacenamiento' => 'tipo de almacenamiento',
            'unidad_medida'       => 'unidad de medida',
            'peso_unitario_kg'    => 'peso unitario',
            'stock_minimo'        => 'stock mínimo',
        ];
    }
}

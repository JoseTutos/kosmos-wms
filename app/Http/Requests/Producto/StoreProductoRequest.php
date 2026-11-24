<?php

namespace App\Http\Requests\Producto;

use App\Enums\TipoAlmacenamiento;
use App\Enums\UnidadMedida;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · CUS-002 Alta de producto
 *
 * Cumple:
 *   - CUS-002 / RS-06: el producto pertenece a un cliente ACTIVO (también lo exige el modelo).
 *   - Tipo y unidad: solo los valores del enum = CHECK de la BD.
 *   - Peso y stock mínimo ≥ 0 y con hasta 3 decimales = NUMERIC(12,3) y (14,3).
 *   - Descripción única por cliente: evita duplicados (la misma regla que la importación CSV).
 *
 * NO recibe 'sku': lo genera el modelo (RS-07) y no es editable.
 * Acepta decimales con coma ("0,015") y los normaliza a punto antes de validar.
 */
class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ADMIN_MAESTROS, vía middleware role
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
        return [
            'cliente_id'          => ['required', 'integer', Rule::exists('clientes', 'id')->where('estado', 'ACTIVO')],
            'descripcion'         => ['required', 'string', 'max:255',
                                      Rule::unique('productos', 'descripcion')->where('cliente_id', $this->integer('cliente_id'))],
            'tipo_almacenamiento' => ['required', new Enum(TipoAlmacenamiento::class)],
            'unidad_medida'       => ['required', new Enum(UnidadMedida::class)],
            'peso_unitario_kg'    => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999'],
            'stock_minimo'        => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:99999999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.exists'   => 'Seleccione un cliente activo.',
            'descripcion.unique'  => 'Este cliente ya tiene un producto con esa descripción.',
        ];
    }

    public function attributes(): array
    {
        return [
            'cliente_id'          => 'cliente',
            'descripcion'         => 'descripción',
            'tipo_almacenamiento' => 'tipo de almacenamiento',
            'unidad_medida'       => 'unidad de medida',
            'peso_unitario_kg'    => 'peso unitario',
            'stock_minimo'        => 'stock mínimo',
        ];
    }
}

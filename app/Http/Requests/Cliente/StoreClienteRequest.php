<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Kosmos WMS · Capa de Presentación (FormRequest) · CUS-001 Alta de cliente
 *
 * Cumple:
 *   - CUS-001: NIT y código corto únicos (UNIQUE en la BD; se validan antes para dar un
 *     mensaje claro en vez de un error SQL).
 *   - NIT: 9 dígitos, sin dígito de verificación (decisión del MVP: no hay integración
 *     con la DIAN; se digita a mano).
 *   - Código corto: 3 a 6 letras mayúsculas, igual que el CHECK de la BD (^[A-Z]{3,6}$).
 *     Es el prefijo de los SKU (ACME-00001).
 *   - Largos máximos = columnas de la migración (200, 255, 30, 150).
 *
 * Normaliza antes de validar: quita espacios y puntos del NIT ("900.123.456" → "900123456")
 * y pasa el código corto a mayúsculas, para que la validación de únicos sea exacta.
 */
class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ADMIN_MAESTROS lo controla el middleware role de la ruta
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nit'          => preg_replace('/[\s.]/', '', (string) $this->nit),
            'codigo_corto' => mb_strtoupper(trim((string) $this->codigo_corto)),
        ]);
    }

    public function rules(): array
    {
        return [
            'nit'             => ['required', 'digits:9', 'unique:clientes,nit'],
            'codigo_corto'    => ['required', 'regex:/^[A-Z]{3,6}$/', 'unique:clientes,codigo_corto'],
            'razon_social'    => ['required', 'string', 'max:200'],
            'direccion'       => ['required', 'string', 'max:255'],
            'telefono'        => ['required', 'string', 'max:30', 'regex:/^[0-9+()\s-]{7,30}$/'],
            'contacto_nombre' => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'nit.digits'         => 'El NIT debe tener exactamente 9 dígitos, sin dígito de verificación.',
            'nit.unique'         => 'Ya existe un cliente con ese NIT.',
            'codigo_corto.regex' => 'El código corto debe tener de 3 a 6 letras (A-Z), sin números, tildes ni espacios.',
            'codigo_corto.unique'=> 'Ese código corto ya lo usa otro cliente.',
            'telefono.regex'     => 'El teléfono solo admite números, espacios y los signos + ( ) -.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nit'             => 'NIT',
            'codigo_corto'    => 'código corto',
            'razon_social'    => 'razón social',
            'direccion'       => 'dirección',
            'telefono'        => 'teléfono',
            'contacto_nombre' => 'nombre de contacto',
        ];
    }
}

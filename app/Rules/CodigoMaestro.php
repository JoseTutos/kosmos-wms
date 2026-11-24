<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * D14 / T7 / N12: los códigos de bodega y ubicación que se crean en Maestros no pueden confundirse
 * con lo que imprime el sistema cuando el escáner los lee:
 *   - no empiezan por PLT- ni BUL- (pallets y bultos)
 *   - no tienen forma de SKU ({CLIENTE}-00000) ni de serial ({SKU}-0000)
 *   - no llevan "/" (separa bodega y ubicación en la etiqueta de la ubicación)
 * Uso: 'codigo' => [..., new CodigoMaestro],
 */
class CodigoMaestro implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $codigo = mb_strtoupper(trim((string) $value));

        if (str_starts_with($codigo, 'PLT-') || str_starts_with($codigo, 'BUL-')) {
            $fail('El código no puede empezar por PLT- ni BUL-: el escáner lo confundiría con un pallet o un bulto.');
        } elseif (preg_match('/^[A-Z]{3,6}-\d{5}(-\d{4})?$/', $codigo)) {
            $fail('El código no puede tener la forma de un SKU o de un serial (ACME-00001).');
        } elseif (str_contains($codigo, '/')) {
            $fail('El código no puede llevar "/": se usa para separar bodega y ubicación en la etiqueta.');
        }
    }
}

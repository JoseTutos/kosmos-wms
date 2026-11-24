<?php

namespace App\Http\Requests\Web;

use App\Models\Bl;
use Illuminate\Validation\Rule;

/** Igual que el alta, sin cliente (no cambia) y con el número de BL único salvo el propio. */
class UpdateBlRequest extends StoreBlRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->bl());
    }

    protected function clienteId(): int
    {
        return (int) $this->bl()->cliente_id;
    }

    public function rules(): array
    {
        $reglas = parent::rules();

        unset($reglas['cliente_id']);
        $reglas['numero_bl'][5] = Rule::unique('bls', 'numero_bl')->ignore($this->bl()->getKey());
        $reglas['quitar_acta'] = ['sometimes', 'boolean'];

        return $reglas;
    }

    private function bl(): Bl
    {
        return $this->route('bl');
    }
}

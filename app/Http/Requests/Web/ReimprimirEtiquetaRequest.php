<?php

namespace App\Http\Requests\Web;

use App\Enums\MotivoReimpresion;
use App\Http\Requests\Api\V1\ReimprimirEtiquetaRequest as ReimprimirApi;
use App\Services\Inbound\EtiquetaService;
use Illuminate\Validation\Rule;

/** Reimpresión web en PDF (E6): los cuatro tipos y motivo siempre obligatorio. */
class ReimprimirEtiquetaRequest extends ReimprimirApi
{
    protected function tipos(): array
    {
        return EtiquetaService::TIPOS;
    }

    protected function prepareForValidation(): void
    {
        $tipo = (string) $this->input('tipo');
        $codigo = trim((string) $this->input('codigo'));

        // Bodega y ubicación los escribe el usuario en Maestros: se respetan tal cual
        $this->merge(['codigo' => in_array($tipo, ['bulto', 'pallet'], true) ? strtoupper($codigo) : $codigo]);
    }

    public function rules(): array
    {
        $reglas = parent::rules();
        $reglas['motivo'] = ['required', Rule::enum(MotivoReimpresion::class)];

        return $reglas;
    }
}

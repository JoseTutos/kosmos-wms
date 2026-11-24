<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kosmos WMS · Capa de Presentación (API Resource) · Representación JSON del usuario
 *
 * Cumple:
 *   - An. §3.1: la salida de la API va por Http/Resources, nunca el modelo directo.
 *
 * Restricción de seguridad (lista blanca):
 *   Solo expone lo que la app necesita para mostrar el nombre y decidir pantallas
 *   por rol. Nunca envía password, intentos_fallidos ni tokens.
 */
class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'nombre'                => $this->nombre,
            'email'                 => $this->email,
            'rol'                   => $this->rol->value,
            'debe_cambiar_password' => $this->debe_cambiar_password,
        ];
    }
}

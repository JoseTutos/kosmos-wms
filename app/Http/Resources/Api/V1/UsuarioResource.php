<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'nombre'                => $this->nombre,
            'email'                 => $this->email,
            'rol'                   => $this->rol->value,
            'debe_cambiar_password' => (bool) $this->debe_cambiar_password,
        ];
    }
}

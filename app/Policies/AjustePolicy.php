<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Ajuste;
use App\Models\User;

/** CUS-019 (J1, RS-54): solo el SUPERVISOR registra y consulta ajustes. Sin update ni delete: solo inserción. */
class AjustePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tieneRol(Rol::SUPERVISOR);
    }

    public function view(User $user, Ajuste $ajuste): bool
    {
        return $user->tieneRol(Rol::SUPERVISOR);
    }

    public function create(User $user): bool
    {
        return $user->tieneRol(Rol::SUPERVISOR);
    }
}

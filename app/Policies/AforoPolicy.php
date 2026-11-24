<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Aforo;
use App\Models\User;

/** CUS-009: el SUPERVISOR activa, revisa, cierra y cancela (web); cualquier OPERARIO cuenta y serializa (app, A9). */
class AforoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tieneRol(Rol::SUPERVISOR) || $user->tieneRol(Rol::OPERARIO);
    }

    public function view(User $user, Aforo $aforo): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->tieneRol(Rol::SUPERVISOR);
    }

    public function cerrar(User $user, Aforo $aforo): bool
    {
        return $user->tieneRol(Rol::SUPERVISOR) && $aforo->estaEnProceso();
    }

    public function cancelar(User $user, Aforo $aforo): bool
    {
        return $this->cerrar($user, $aforo);
    }

    /** El estado (EN_PROCESO) lo valida el dominio para que la app reciba AFORO_NO_EN_PROCESO y no un 403. */
    public function contar(User $user, Aforo $aforo): bool
    {
        return $user->tieneRol(Rol::OPERARIO);
    }

    public function reporte(User $user, Aforo $aforo): bool
    {
        return $user->tieneRol(Rol::SUPERVISOR) && filled($aforo->reporte_path);
    }
}

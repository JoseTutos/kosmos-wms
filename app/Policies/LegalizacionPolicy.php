<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Legalizacion;
use App\Models\User;

/**
 * CUS-010 (L1): legalizan ANALISTA y SUPERVISOR; el OPERARIO no tiene acceso.
 * No hay update ni delete: la LEG es inmutable (RS-48, L18).
 * CUS-011 Estado legal y CUS-012 Históricos: SUPERVISOR, ANALISTA y GERENTE, solo lectura (C4, RS-51, RS-53).
 */
class LegalizacionPolicy
{
    private const ROLES = [Rol::ANALISTA, Rol::SUPERVISOR];

    private const ROLES_CONSULTA = [Rol::SUPERVISOR, Rol::ANALISTA, Rol::GERENTE];

    public function viewAny(User $user): bool
    {
        return $user->tieneRol(...self::ROLES);
    }

    public function view(User $user, Legalizacion $legalizacion): bool
    {
        return $user->tieneRol(...self::ROLES);
    }

    public function create(User $user): bool
    {
        return $user->tieneRol(...self::ROLES);
    }

    /** CUS-011 (C4). */
    public function verEstadoLegal(User $user): bool
    {
        return $user->tieneRol(...self::ROLES_CONSULTA);
    }

    /** CUS-012 (H1): histórico, detalle, soporte y exportes. */
    public function verHistorico(User $user): bool
    {
        return $user->tieneRol(...self::ROLES_CONSULTA);
    }
}

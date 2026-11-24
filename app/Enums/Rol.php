<?php

namespace App\Enums;

/** users.rol — un rol excluyente por usuario (RS-19). */
enum Rol: string
{
    case ADMIN_SISTEMA  = 'ADMIN_SISTEMA';
    case ADMIN_MAESTROS = 'ADMIN_MAESTROS';
    case SUPERVISOR     = 'SUPERVISOR';
    case ANALISTA       = 'ANALISTA';
    case OPERARIO       = 'OPERARIO';
    case GERENTE        = 'GERENTE';
}

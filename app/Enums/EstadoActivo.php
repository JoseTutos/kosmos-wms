<?php

namespace App\Enums;

/** clientes.estado / productos.estado — sin borrado físico, solo inactivación (RS-04). */
enum EstadoActivo: string
{
    case ACTIVO   = 'ACTIVO';
    case INACTIVO = 'INACTIVO';
}

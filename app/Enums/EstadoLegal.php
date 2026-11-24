<?php

namespace App\Enums;

/** movimientos.estado_legal / ajustes.estado_afectado */
enum EstadoLegal: string
{
    case CUARENTENA = 'CUARENTENA';
    case LEGALIZADA = 'LEGALIZADA';
}

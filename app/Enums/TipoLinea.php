<?php

namespace App\Enums;

enum TipoLinea: string
{
    case COMPLETO = 'COMPLETO';
    case PARCIAL  = 'PARCIAL';
    case SERIAL   = 'SERIAL';
}

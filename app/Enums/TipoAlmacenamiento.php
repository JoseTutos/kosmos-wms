<?php

namespace App\Enums;

enum TipoAlmacenamiento: string
{
    case PALETIZADO    = 'PALETIZADO';
    case GRANEL        = 'GRANEL';
    case CONTENERIZADO = 'CONTENERIZADO';
    case ENSAMBLADO    = 'ENSAMBLADO';
    case MEZCLA        = 'MEZCLA';
    case EMPACADO      = 'EMPACADO';
}

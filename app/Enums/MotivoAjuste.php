<?php

namespace App\Enums;

enum MotivoAjuste: string
{
    case ERROR_CONTEO        = 'ERROR_CONTEO';
    case DANO                = 'DANO';
    case FALTANTE            = 'FALTANTE';
    case SOBRANTE            = 'SOBRANTE';
    case CORRECCION_REGISTRO = 'CORRECCION_REGISTRO';
    case OTRO                = 'OTRO';
}

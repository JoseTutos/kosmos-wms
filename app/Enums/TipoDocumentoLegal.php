<?php

namespace App\Enums;

enum TipoDocumentoLegal: string
{
    case RESOLUCION_NACIONALIZACION = 'RESOLUCION_NACIONALIZACION';
    case VOBO                       = 'VOBO';
    case DECLARACION_IMPORTACION    = 'DECLARACION_IMPORTACION';
    case OTRO                       = 'OTRO';
}

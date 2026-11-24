<?php

namespace App\Enums;

enum TipoMovimiento: string
{
    case SALDO_INICIAL = 'SALDO_INICIAL';
    case RECEPCION     = 'RECEPCION';
    case TRASLADO      = 'TRASLADO';
    case AFORO         = 'AFORO';
    case LEGALIZACION  = 'LEGALIZACION';
    case AJUSTE        = 'AJUSTE';
    case DESPACHO      = 'DESPACHO';
}

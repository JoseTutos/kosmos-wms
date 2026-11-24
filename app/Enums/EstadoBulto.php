<?php

namespace App\Enums;

use App\Enums\Concerns\ValidaTransiciones;
use App\Enums\Contracts\MaquinaEstados;

/** bultos.estado — diagrama de estados 3.1.2 b) */
enum EstadoBulto: string implements MaquinaEstados
{
    use ValidaTransiciones;

    case RECIBIDO   = 'RECIBIDO';
    case UBICADO    = 'UBICADO';
    case EN_AFORO   = 'EN_AFORO';
    case AFORADO    = 'AFORADO';
    case DESPACHADO = 'DESPACHADO';
    case ANULADO    = 'ANULADO';

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::RECIBIDO   => [self::UBICADO, self::ANULADO],
            self::UBICADO    => [self::UBICADO, self::EN_AFORO, self::DESPACHADO],
            self::EN_AFORO   => [self::AFORADO, self::UBICADO],
            self::AFORADO    => [self::DESPACHADO],
            self::DESPACHADO => [],
            self::ANULADO    => [],
        };
    }
}

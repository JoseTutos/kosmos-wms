<?php

namespace App\Enums;

use App\Enums\Concerns\ValidaTransiciones;
use App\Enums\Contracts\MaquinaEstados;

/** bls.estado — diagrama de estados 3.1.2 a) */
enum EstadoBl: string implements MaquinaEstados
{
    use ValidaTransiciones;

    case PROGRAMADO         = 'PROGRAMADO';
    case EN_RECEPCION       = 'EN_RECEPCION';
    case RECIBIDO           = 'RECIBIDO';
    case DESPACHADO_PARCIAL = 'DESPACHADO_PARCIAL';
    case CERRADO            = 'CERRADO';

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::PROGRAMADO         => [self::EN_RECEPCION],
            self::EN_RECEPCION       => [self::RECIBIDO],
            self::RECIBIDO           => [self::DESPACHADO_PARCIAL, self::CERRADO],
            self::DESPACHADO_PARCIAL => [self::DESPACHADO_PARCIAL, self::CERRADO],
            self::CERRADO            => [],
        };
    }
}

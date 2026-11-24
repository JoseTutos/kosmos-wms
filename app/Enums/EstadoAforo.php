<?php

namespace App\Enums;

use App\Enums\Concerns\ValidaTransiciones;
use App\Enums\Contracts\MaquinaEstados;

enum EstadoAforo: string implements MaquinaEstados
{
    use ValidaTransiciones;

    case EN_PROCESO = 'EN_PROCESO';
    case CERRADO    = 'CERRADO';
    case CANCELADO  = 'CANCELADO';

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::EN_PROCESO => [self::CERRADO, self::CANCELADO],
            self::CERRADO    => [],
            self::CANCELADO  => [],
        };
    }
}

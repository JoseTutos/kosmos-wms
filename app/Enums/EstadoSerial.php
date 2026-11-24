<?php

namespace App\Enums;

use App\Enums\Concerns\ValidaTransiciones;
use App\Enums\Contracts\MaquinaEstados;

enum EstadoSerial: string implements MaquinaEstados
{
    use ValidaTransiciones;

    case DISPONIBLE = 'DISPONIBLE';
    case RESERVADO  = 'RESERVADO';
    case DESPACHADO = 'DESPACHADO';

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::DISPONIBLE => [self::RESERVADO],
            self::RESERVADO  => [self::DISPONIBLE, self::DESPACHADO],
            self::DESPACHADO => [],
        };
    }
}

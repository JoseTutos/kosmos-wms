<?php

namespace App\Enums;

use App\Enums\Concerns\ValidaTransiciones;
use App\Enums\Contracts\MaquinaEstados;

enum EstadoUsuario: string implements MaquinaEstados
{
    use ValidaTransiciones;

    case ACTIVO    = 'ACTIVO';
    case INACTIVO  = 'INACTIVO';
    case BLOQUEADO = 'BLOQUEADO';

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::ACTIVO    => [self::INACTIVO, self::BLOQUEADO],
            self::BLOQUEADO => [self::ACTIVO, self::INACTIVO],
            self::INACTIVO  => [self::ACTIVO],
        };
    }
}

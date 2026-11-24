<?php

namespace App\Enums;

use App\Enums\Concerns\ValidaTransiciones;
use App\Enums\Contracts\MaquinaEstados;

/** pallets.estado — diagrama de estados 3.1.2 c) */
enum EstadoPallet: string implements MaquinaEstados
{
    use ValidaTransiciones;

    case ABIERTO    = 'ABIERTO';
    case CERRADO    = 'CERRADO';
    case UBICADO    = 'UBICADO';
    case DESPACHADO = 'DESPACHADO';

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::ABIERTO    => [self::CERRADO],
            self::CERRADO    => [self::UBICADO],
            self::UBICADO    => [self::UBICADO, self::DESPACHADO],
            self::DESPACHADO => [],
        };
    }
}

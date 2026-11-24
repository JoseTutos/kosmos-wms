<?php

namespace App\Enums;

use App\Enums\Concerns\ValidaTransiciones;
use App\Enums\Contracts\MaquinaEstados;

/** ordenes_salida.estado — diagrama de estados 3.1.2 d) */
enum EstadoOrdenSalida: string implements MaquinaEstados
{
    use ValidaTransiciones;

    case PENDIENTE_PICKING  = 'PENDIENTE_PICKING';
    case EN_PICKING         = 'EN_PICKING';
    case DESPACHADO         = 'DESPACHADO';
    case DESPACHADO_PARCIAL = 'DESPACHADO_PARCIAL';
    case ANULADA            = 'ANULADA';

    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::PENDIENTE_PICKING  => [self::EN_PICKING, self::ANULADA],
            self::EN_PICKING         => [self::DESPACHADO, self::DESPACHADO_PARCIAL],
            self::DESPACHADO         => [],
            self::DESPACHADO_PARCIAL => [],
            self::ANULADA            => [],
        };
    }
}

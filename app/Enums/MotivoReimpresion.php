<?php

namespace App\Enums;

/** E4 / RS-35 a RS-37: motivo obligatorio de toda reimpresión de etiqueta. OTRO exige texto. */
enum MotivoReimpresion: string
{
    case DANADA      = 'DANADA';
    case NO_IMPRIMIO = 'NO_IMPRIMIO';
    case ILEGIBLE    = 'ILEGIBLE';
    case OTRO        = 'OTRO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::DANADA      => 'Dañada',
            self::NO_IMPRIMIO => 'No imprimió',
            self::ILEGIBLE    => 'Ilegible',
            self::OTRO        => 'Otro',
        };
    }
}

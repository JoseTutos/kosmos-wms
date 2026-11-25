<?php

namespace App\Enums;

/** ajustes.motivo (J4). CORRECCION_REGISTRO exige el movimiento que corrige (J6, RS-57). */
enum MotivoAjuste: string
{
    case ERROR_CONTEO        = 'ERROR_CONTEO';
    case DANO                = 'DANO';
    case FALTANTE            = 'FALTANTE';
    case SOBRANTE            = 'SOBRANTE';
    case CORRECCION_REGISTRO = 'CORRECCION_REGISTRO';
    case OTRO                = 'OTRO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ERROR_CONTEO        => 'Error de conteo',
            self::DANO                => 'Daño',
            self::FALTANTE            => 'Faltante',
            self::SOBRANTE            => 'Sobrante',
            self::CORRECCION_REGISTRO => 'Corrección de registro',
            self::OTRO                => 'Otro',
        };
    }

    public function exigeMovimientoCorregido(): bool
    {
        return $this === self::CORRECCION_REGISTRO;
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return array_column(array_map(fn (self $c) => [$c->value, $c->etiqueta()], self::cases()), 1, 0);
    }
}

<?php

namespace App\Enums;

/** movimientos.estado_legal / ajustes.estado_afectado */
enum EstadoLegal: string
{
    case CUARENTENA = 'CUARENTENA';
    case LEGALIZADA = 'LEGALIZADA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::CUARENTENA => 'En cuarentena',
            self::LEGALIZADA => 'Legalizada',
        };
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return array_column(array_map(fn (self $c) => [$c->value, $c->etiqueta()], self::cases()), 1, 0);
    }
}

<?php

namespace App\Enums;

/** ajustes.tipo (J2). La cantidad del ajuste va sin signo: el tipo dice si suma o resta. */
enum TipoAjuste: string
{
    case POSITIVO = 'POSITIVO';
    case NEGATIVO = 'NEGATIVO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::POSITIVO => 'Positivo (suma)',
            self::NEGATIVO => 'Negativo (resta)',
        };
    }

    public function signo(): int
    {
        return $this === self::POSITIVO ? 1 : -1;
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return array_column(array_map(fn (self $c) => [$c->value, $c->etiqueta()], self::cases()), 1, 0);
    }
}

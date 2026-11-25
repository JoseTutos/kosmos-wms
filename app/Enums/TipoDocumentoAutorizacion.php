<?php

namespace App\Enums;

/** legalizaciones.tipo_documento (L11). "Otro" no lleva campo adicional: lo identifica el número. */
enum TipoDocumentoAutorizacion: string
{
    case RESOLUCION_NACIONALIZACION = 'RESOLUCION_NACIONALIZACION';
    case VOBO                       = 'VOBO';
    case DECLARACION_IMPORTACION    = 'DECLARACION_IMPORTACION';
    case OTRO                       = 'OTRO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::RESOLUCION_NACIONALIZACION => 'Resolución de nacionalización',
            self::VOBO                       => 'Visto bueno (VoBo)',
            self::DECLARACION_IMPORTACION    => 'Declaración de importación',
            self::OTRO                       => 'Otro',
        };
    }

    /** @return array<string, string> valor => etiqueta, para los select */
    public static function opciones(): array
    {
        return array_column(array_map(fn (self $c) => [$c->value, $c->etiqueta()], self::cases()), 1, 0);
    }
}

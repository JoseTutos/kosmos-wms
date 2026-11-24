<?php

namespace App\Support;

use App\Enums\EstadoActiva;
use App\Enums\EstadoActivo;
use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use App\Enums\TipoAlmacenamiento;
use App\Enums\UnidadMedida;

/**
 * Kosmos WMS · Soporte de presentación · Textos legibles de enums y datos del usuario
 *
 * Por qué existe: los enums guardan valores técnicos (ADMIN_SISTEMA). Las vistas
 * muestran "Administrador del Sistema". Centralizarlo evita textos distintos
 * en cada pantalla.
 */
final class Etiquetas
{
    public static function rol(Rol $rol): string
    {
        return match ($rol) {
            Rol::ADMIN_SISTEMA  => 'Administrador del Sistema',
            Rol::ADMIN_MAESTROS => 'Administrador de Maestros',
            Rol::SUPERVISOR     => 'Supervisor',
            Rol::ANALISTA       => 'Analista',
            Rol::OPERARIO       => 'Operario',
            Rol::GERENTE        => 'Gerente',
        };
    }

    /** Versión corta para columnas de tabla. */
    public static function rolCorto(Rol $rol): string
    {
        return match ($rol) {
            Rol::ADMIN_SISTEMA  => 'Admin Sistema',
            Rol::ADMIN_MAESTROS => 'Admin Maestros',
            default             => self::rol($rol),
        };
    }

    /** Qué hace cada rol (formulario de usuario): evita asignar el rol equivocado (RS-19, RE-7). */
    public static function descripcionRol(Rol $rol): string
    {
        return match ($rol) {
            Rol::ADMIN_SISTEMA  => 'Usuarios, roles y bitácora. No gestiona maestros.',
            Rol::ADMIN_MAESTROS => 'Clientes, productos, bodegas y ubicaciones.',
            Rol::SUPERVISOR     => 'BL, aforos, ajustes y cierres de despacho. Web y app.',
            Rol::ANALISTA       => 'Legalizaciones, órdenes de salida y consultas.',
            Rol::OPERARIO       => 'Solo app móvil: recepción, ubicación, aforo y picking.',
            Rol::GERENTE        => 'Consultas, reportes y bitácora. Solo lectura.',
        };
    }

    public static function estado(EstadoUsuario $estado): string
    {
        return match ($estado) {
            EstadoUsuario::ACTIVO    => 'Activo',
            EstadoUsuario::INACTIVO  => 'Inactivo',
            EstadoUsuario::BLOQUEADO => 'Bloqueado',
        };
    }

    /** Clientes y productos (ACTIVO/INACTIVO) o bodegas y ubicaciones (ACTIVA/INACTIVA). */
    public static function activo(EstadoActivo|EstadoActiva $estado): string
    {
        return match ($estado) {
            EstadoActivo::ACTIVO   => 'Activo',
            EstadoActivo::INACTIVO => 'Inactivo',
            EstadoActiva::ACTIVA   => 'Activa',
            EstadoActiva::INACTIVA => 'Inactiva',
        };
    }

    public static function tipoAlmacenamiento(TipoAlmacenamiento $tipo): string
    {
        return match ($tipo) {
            TipoAlmacenamiento::PALETIZADO    => 'Paletizado',
            TipoAlmacenamiento::GRANEL        => 'Granel',
            TipoAlmacenamiento::CONTENERIZADO => 'Contenerizado',
            TipoAlmacenamiento::ENSAMBLADO    => 'Ensamblado',
            TipoAlmacenamiento::MEZCLA        => 'Mezcla',
            TipoAlmacenamiento::EMPACADO      => 'Empacado',
        };
    }

    /** Usa ->value para no depender de los nombres de los casos del enum (UND, KG, LT). */
    public static function unidadMedida(UnidadMedida $unidad): string
    {
        return match ($unidad->value) {
            'UND'   => 'Unidades (UND)',
            'KG'    => 'Kilogramos (KG)',
            'LT'    => 'Litros (LT)',
            default => $unidad->value,
        };
    }

    /** "Carlos Pérez" → "CP" (avatar). */
    public static function iniciales(string $nombre): string
    {
        $partes = preg_split('/\s+/u', trim($nombre)) ?: [];

        return mb_strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
    }
}

<?php

namespace App\Exceptions;

use App\Enums\CodigoError;
use RuntimeException;

/**
 * Violación de una regla de negocio (RS) o de una invariante del dominio.
 * La convierte bootstrap/app.php: API → 422 { mensaje, codigo, datos } · Web → back()->withErrors.
 *
 * Dos formas (compatible con el código de Maestros):
 *   throw new ReglaNegocioException(CodigoError::PALLET_CERRADO, datos: ['pallet' => $p->codigo]);
 *   throw new ReglaNegocioException('Solo se importan productos para clientes ACTIVOS.');   // codigo REGLA_NEGOCIO
 */
final class ReglaNegocioException extends RuntimeException
{
    public readonly CodigoError $codigo;

    /**
     * @param array<string, mixed> $datos  contexto para la app (códigos, cantidades); nunca datos sensibles
     * @param string               $campo  clave del error en la web (la vista lo muestra con @error)
     */
    public function __construct(
        CodigoError|string $codigo,
        ?string $mensaje = null,
        public readonly array $datos = [],
        public readonly string $campo = 'negocio',
    ) {
        if (is_string($codigo)) {
            $mensaje ??= $codigo;
            $codigo = CodigoError::REGLA_NEGOCIO;
        }

        $this->codigo = $codigo;

        parent::__construct($mensaje ?? $codigo->mensaje());
    }
}

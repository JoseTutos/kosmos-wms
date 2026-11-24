<?php

namespace App\Support;

use App\Enums\CodigoError;
use Illuminate\Http\JsonResponse;

/** Única forma de construir una respuesta de error de /api/v1 (mismo contrato en todo lado). */
final class ErrorApi
{
    /** @param array<string, mixed> $extra  'errores' (validación) o 'datos' (regla de negocio) */
    public static function respuesta(CodigoError $codigo, ?string $mensaje = null, int $estado = 422, array $extra = []): JsonResponse
    {
        return response()->json(
            ['mensaje' => $mensaje ?: $codigo->mensaje(), 'codigo' => $codigo->value] + $extra,
            $estado,
        );
    }
}

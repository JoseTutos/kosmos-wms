<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * Llamadas a /api/v1 como lo hace la app: Bearer token + Idempotency-Key nueva en cada operación (D13).
 * Se usa junto con CreaUsuarios o CreaEscenarioInbound.
 */
trait UsaApi
{
    /** @var array<int, string> */
    private array $tokensApi = [];

    protected function apiPost(User $usuario, string $uri, array $datos = [], ?string $clave = null): TestResponse
    {
        return $this->comoApp($usuario)
            ->withHeader('Idempotency-Key', $clave ?? (string) Str::uuid())
            ->postJson($uri, $datos);
    }

    protected function apiGet(User $usuario, string $uri): TestResponse
    {
        return $this->comoApp($usuario)->getJson($uri);
    }

    protected function apiDelete(User $usuario, string $uri): TestResponse
    {
        return $this->comoApp($usuario)->deleteJson($uri);
    }

    private function comoApp(User $usuario): static
    {
        // Sanctum guarda el usuario resuelto entre peticiones del mismo test: se olvida para cambiar de operario
        $this->app['auth']->forgetGuards();

        // withHeader() persiste entre peticiones: sin esto, un DELETE reusaría la Idempotency-Key del POST anterior
        $this->flushHeaders();

        $this->tokensApi[$usuario->id] ??= $usuario->createToken('Equipo de prueba')->plainTextToken;

        return $this->withToken($this->tokensApi[$usuario->id]);
    }
}

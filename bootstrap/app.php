<?php

use App\Enums\CodigoError;
use App\Exceptions\ReglaNegocioException;
use App\Support\ErrorApi;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'             => \App\Http\Middleware\RoleMiddleware::class,
            'cuenta.activa'    => \App\Http\Middleware\EnsureCuentaActiva::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'idempotencia'     => \App\Http\Middleware\Idempotencia::class, // D13
        ]);

        // Usuario ya autenticado que abre /login → al inicio.
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        // Solo nginx habla con la app (Docker Compose). Si cambia la topología, restrinja la IP.
        $middleware->trustProxies(at: '*');

          // S1 (Módulo 3): máximo absoluto de 12 h para la sesión web, además de los 120 min de inactividad.
        $middleware->web(append: [\App\Http\Middleware\SesionAbsoluta::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $esApi = fn (Request $request) => $request->is('api/*');

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $esApi($request) || $request->expectsJson()
        );

        // Una regla de negocio incumplida no es un fallo del sistema: no va al log.
        $exceptions->dontReport(ReglaNegocioException::class);

        // ---- Regla de negocio: 422 JSON en la API · back()->withErrors en la web ----
        $exceptions->render(function (ReglaNegocioException $e, Request $request) use ($esApi) {
            if ($esApi($request) || $request->expectsJson()) {
                return ErrorApi::respuesta($e->codigo, $e->getMessage(), 422, ['datos' => (object) $e->datos]);
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'password_actual', 'current_password']))
                ->withErrors([$e->campo => $e->getMessage()]);
        });

        // ---- Contrato único de errores de /api/v1 (la web conserva el comportamiento de Laravel) ----
        $exceptions->render(function (ValidationException $e, Request $request) use ($esApi) {
            if (! $esApi($request)) {
                return null;
            }

            $errores = $e->errors();

            return ErrorApi::respuesta(CodigoError::VALIDACION, collect($errores)->flatten()->first(), $e->status, ['errores' => $errores]);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($esApi) {
            return $esApi($request) ? ErrorApi::respuesta(CodigoError::NO_AUTENTICADO, estado: 401) : null;
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($esApi) {
            if (! $esApi($request)) {
                return null;
            }

            $estado = $e->getStatusCode();

            [$codigo, $mensaje] = match ($estado) {
                403     => [CodigoError::SIN_PERMISO, $e->getMessage()],   // RoleMiddleware ya trae el texto
                404     => [CodigoError::NO_ENCONTRADO, null],             // no revela "No query results for model [...]"
                405     => [CodigoError::METODO_NO_PERMITIDO, null],
                429     => [CodigoError::DEMASIADOS_INTENTOS, null],
                default => [CodigoError::ERROR_HTTP, $e->getMessage()],
            };

            return ErrorApi::respuesta($codigo, $mensaje, $estado)->withHeaders($e->getHeaders());
        });

        // ---- Cualquier otro error en producción: sin traza en la respuesta (sí queda en laravel.log, L3) ----
        $exceptions->render(function (Throwable $e, Request $request) use ($esApi) {
            if (! $esApi($request) || config('app.debug') || $e instanceof HttpResponseException) {
                return null;
            }

            return ErrorApi::respuesta(CodigoError::ERROR_INTERNO, estado: 500);
        });
    })
    ->create();

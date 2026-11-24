<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ... lo que ya tenga

        // /api/v1 autenticado: por usuario (escaneo continuo).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(config('kosmos.api.peticiones_por_minuto'))
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        // Login de la app: por correo + IP (el bloqueo a los 5 fallos es aparte, en el dominio).
        RateLimiter::for('login-api', fn (Request $request) => Limit::perMinute(config('kosmos.api.login_por_minuto'))
            ->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));
    }
}

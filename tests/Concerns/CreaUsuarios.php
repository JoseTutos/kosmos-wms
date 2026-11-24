<?php

namespace Tests\Concerns;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Str;

/** Crea usuarios sin depender de UserFactory (el modelo usa nombre/rol/estado, no el esquema por defecto). */
trait CreaUsuarios
{
    protected const CLAVE = 'Clave-Prueba-2026';

    /** @param array<string, mixed> $atributos  p. ej. ['estado' => EstadoUsuario::INACTIVO, 'debe_cambiar_password' => true] */
    protected function crearUsuario(Rol $rol, array $atributos = []): User
    {
        $usuario = new User([
            'nombre' => 'Prueba '.$rol->value,
            'email'  => Str::lower(Str::random(12)).'@kosmos.test',
            'rol'    => $rol,
        ]);

        $usuario->forceFill(array_merge([
            'password'              => self::CLAVE,
            'debe_cambiar_password' => false,
        ], $atributos))->save();

        return $usuario->refresh();
    }

    protected function tokenPara(User $usuario, string $dispositivo = 'Equipo de prueba'): string
    {
        return $usuario->createToken($dispositivo)->plainTextToken;
    }

    /** Laravel conserva el usuario resuelto entre peticiones del mismo test; esto lo olvida. */
    protected function olvidarAutenticacion(): void
    {
        $this->app['auth']->forgetGuards();
    }
}

<?php

namespace Tests\Feature\Seguridad;

use App\Enums\Rol;
use App\Http\Middleware\SesionAbsoluta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** S1: máximo absoluto de 12 h para la sesión web, además de los 120 min de inactividad. */
final class SesionAbsolutaTest extends TestCase
{
    use CreaUsuarios, RefreshDatabase;

    public function test_la_primera_peticion_marca_el_inicio_de_la_sesion(): void
    {
        $usuario = $this->crearUsuario(Rol::SUPERVISOR);

        $this->actingAs($usuario)->get(route('dashboard'))->assertOk();

        $this->assertSame($usuario->id, session(SesionAbsoluta::CLAVE)['usuario']);
    }

    public function test_dentro_del_maximo_la_sesion_sigue_activa(): void
    {
        $usuario = $this->crearUsuario(Rol::SUPERVISOR);

        $this->actingAs($usuario)
            ->withSession([SesionAbsoluta::CLAVE => ['usuario' => $usuario->id, 'desde' => now()->subHours(11)->getTimestamp()]])
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_al_superar_las_12_horas_cierra_la_sesion_y_guarda_la_pantalla(): void
    {
        $usuario = $this->crearUsuario(Rol::SUPERVISOR);

        $this->actingAs($usuario)
            ->withSession([SesionAbsoluta::CLAVE => ['usuario' => $usuario->id, 'desde' => now()->subHours(13)->getTimestamp()]])
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(route('dashboard'), session('url.intended'));
    }

    public function test_la_marca_de_otro_usuario_no_cuenta(): void
    {
        $usuario = $this->crearUsuario(Rol::SUPERVISOR);

        $this->actingAs($usuario)
            ->withSession([SesionAbsoluta::CLAVE => ['usuario' => $usuario->id + 999, 'desde' => now()->subHours(13)->getTimestamp()]])
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($usuario);
    }
}

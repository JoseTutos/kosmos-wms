<?php

namespace Tests\Feature\Api\V1;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** Las acciones del ADMIN_SISTEMA sobre un usuario cierran su sesión en la app (promesa de los modales de Usuarios). */
final class RevocacionTokensTest extends TestCase
{
    use CreaUsuarios, RefreshDatabase;

    private User $admin;

    private User $operario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->crearUsuario(Rol::ADMIN_SISTEMA);
        $this->operario = $this->crearUsuario(Rol::OPERARIO);
        $this->tokenPara($this->operario, 'Equipo A');
        $this->tokenPara($this->operario, 'Equipo B');
    }

    public function test_bloquear_revoca_los_tokens(): void
    {
        $this->actingAs($this->admin)->patch(route('usuarios.bloquear', $this->operario))->assertRedirect();

        $this->assertSame(0, $this->operario->tokens()->count());
    }

    public function test_inactivar_revoca_los_tokens(): void
    {
        $this->actingAs($this->admin)->patch(route('usuarios.inactivar', $this->operario))->assertRedirect();

        $this->assertSame(0, $this->operario->tokens()->count());
    }

    public function test_restablecer_la_clave_revoca_los_tokens(): void
    {
        $this->actingAs($this->admin)->patch(route('usuarios.reset-password', $this->operario))->assertRedirect();

        $this->assertSame(0, $this->operario->tokens()->count());
        $this->assertTrue($this->operario->refresh()->debe_cambiar_password);
    }

    public function test_cambiar_el_rol_revoca_los_tokens(): void
    {
        $this->actingAs($this->admin)->put(route('usuarios.update', $this->operario), [
            'nombre' => $this->operario->nombre,
            'email'  => $this->operario->email,
            'rol'    => Rol::SUPERVISOR->value,
        ])->assertRedirect();

        $this->assertSame(0, $this->operario->tokens()->count());
    }
}

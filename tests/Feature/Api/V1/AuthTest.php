<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EstadoUsuario;
use App\Enums\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use CreaUsuarios, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ruta de operación ficticia: mismo apilado de middleware que tendrán las del Paso 4.
        Route::middleware(['api', 'auth:sanctum', 'cuenta.activa', 'role:OPERARIO', 'password.changed', 'idempotencia'])
            ->post('api/v1/_prueba/operacion', fn () => response()->json(['data' => ['ok' => true]], 201));
    }

    private function login(string $email, string $password = self::CLAVE, string $dispositivo = 'Zebra TC21'): TestResponse
    {
        return $this->postJson('/api/v1/auth/login', compact('email', 'password', 'dispositivo'));
    }

    // ------------------------------------------------------------ login

    public function test_operario_obtiene_token(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO);

        $this->login(Str::upper($op->email)) // el correo no distingue mayúsculas
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'tipo', 'expira_en', 'usuario' => ['id', 'nombre', 'email', 'rol', 'debe_cambiar_password']]])
            ->assertJsonPath('data.tipo', 'Bearer')
            ->assertJsonPath('data.usuario.rol', 'OPERARIO');

        $this->assertSame(1, $op->tokens()->count());
        $this->assertNotNull($op->tokens()->first()->expires_at);
    }

    public function test_volver_a_entrar_desde_el_mismo_dispositivo_reemplaza_el_token(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO);

        $this->login($op->email)->assertOk();
        $this->login($op->email)->assertOk();
        $this->login($op->email, dispositivo: 'Otro equipo')->assertOk();

        $this->assertSame(2, $op->tokens()->count());
    }

    #[DataProvider('rolesSinApp')]
    public function test_la_app_solo_acepta_operarios(string $rol): void
    {
        $usuario = $this->crearUsuario(Rol::from($rol));

        $this->login($usuario->email)
            ->assertStatus(422)
            ->assertJsonPath('codigo', 'ROL_SIN_ACCESO');

        $this->assertSame(0, $usuario->tokens()->count());
    }

    public static function rolesSinApp(): array
    {
        return [['SUPERVISOR'], ['ADMIN_SISTEMA'], ['ADMIN_MAESTROS'], ['ANALISTA'], ['GERENTE']];
    }

    public function test_clave_incorrecta_y_correo_inexistente_responden_igual(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO);

        $mal = $this->login($op->email, 'otra-clave')->assertStatus(422)->assertJsonPath('codigo', 'CREDENCIALES_INVALIDAS');
        $nadie = $this->login('nadie@kosmos.test')->assertStatus(422)->assertJsonPath('codigo', 'CREDENCIALES_INVALIDAS');

        $this->assertSame($mal->json('mensaje'), $nadie->json('mensaje'));
    }

    public function test_cinco_fallos_bloquean_la_cuenta_y_revocan_sus_tokens(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO);
        $this->tokenPara($op);

        for ($i = 0; $i < 5; $i++) {
            $this->login($op->email, 'otra-clave')->assertStatus(422);
        }

        $this->assertSame(EstadoUsuario::BLOQUEADO, $op->refresh()->estado);
        $this->assertSame(0, $op->tokens()->count());

        // Con la clave correcta ya se le puede decir por qué no entra.
        $this->login($op->email)->assertStatus(422)->assertJsonPath('codigo', 'USUARIO_BLOQUEADO');
    }

    public function test_usuario_inactivo_es_rechazado(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO, ['estado' => EstadoUsuario::INACTIVO]);

        $this->login($op->email)->assertStatus(422)->assertJsonPath('codigo', 'USUARIO_INACTIVO');
        $this->assertSame(0, $op->tokens()->count());
    }

    public function test_login_incompleto_devuelve_el_contrato_de_validacion(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('codigo', 'VALIDACION')
            ->assertJsonStructure(['mensaje', 'codigo', 'errores' => ['email', 'password', 'dispositivo']]);
    }

    // ------------------------------------------------------------ me / logout

    public function test_sin_token_responde_401_json_aunque_no_pida_json(): void
    {
        $this->get('/api/v1/auth/me') // sin Accept: application/json
            ->assertStatus(401)
            ->assertJsonPath('codigo', 'NO_AUTENTICADO');
    }

    public function test_me_devuelve_el_usuario_del_token(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO, ['debe_cambiar_password' => true]);

        $this->withToken($this->tokenPara($op))->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $op->email)
            ->assertJsonPath('data.debe_cambiar_password', true);
    }

    public function test_logout_revoca_solo_el_token_actual(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO);
        $actual = $this->tokenPara($op, 'Equipo A');
        $this->tokenPara($op, 'Equipo B');

        $this->withToken($actual)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertSame(['Equipo B'], $op->tokens()->pluck('name')->all());

        $this->olvidarAutenticacion();
        $this->withToken($actual)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_token_de_cuenta_inactivada_responde_cuenta_no_activa(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO);
        $token = $this->tokenPara($op);

        // Cambio directo en BD (sin pasar por UsuarioController, que ya revoca).
        DB::table('users')->where('id', $op->id)->update(['estado' => EstadoUsuario::INACTIVO->value]);

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('codigo', 'CUENTA_NO_ACTIVA');

        $this->assertSame(0, $op->tokens()->count());
    }

    public function test_token_de_otro_rol_no_opera(): void
    {
        // Un SUPERVISOR no obtiene token por login; si lo tuviera (creado a mano), tampoco opera.
        $sup = $this->crearUsuario(Rol::SUPERVISOR);

        $this->withToken($this->tokenPara($sup))->getJson('/api/v1/auth/me')
            ->assertStatus(403)
            ->assertJsonPath('codigo', 'SIN_PERMISO');
    }

    // ------------------------------------------------------------ clave temporal

    public function test_clave_temporal_bloquea_la_operacion(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO, ['debe_cambiar_password' => true]);

        $this->withToken($this->tokenPara($op))
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/_prueba/operacion')
            ->assertStatus(403)
            ->assertJsonPath('codigo', 'PASSWORD_TEMPORAL');
    }

    public function test_cambiar_la_clave_habilita_la_operacion_y_conserva_solo_este_token(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO, ['debe_cambiar_password' => true]);
        $actual = $this->tokenPara($op, 'Equipo A');
        $this->tokenPara($op, 'Equipo B');

        $this->withToken($actual)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->putJson('/api/v1/auth/password', [
                'password_actual'       => self::CLAVE,
                'password'              => 'NuevaClave2026',
                'password_confirmation' => 'NuevaClave2026',
            ])
            ->assertOk()
            ->assertJsonPath('data.debe_cambiar_password', false);

        $this->assertSame(['Equipo A'], $op->tokens()->pluck('name')->all());

        $this->olvidarAutenticacion();
        $this->withToken($actual)
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->postJson('/api/v1/_prueba/operacion')
            ->assertCreated();
    }

    public function test_clave_actual_incorrecta_no_cambia_nada(): void
    {
        $op = $this->crearUsuario(Rol::OPERARIO, ['debe_cambiar_password' => true]);

        $this->withToken($this->tokenPara($op))
            ->withHeader('Idempotency-Key', (string) Str::uuid())
            ->putJson('/api/v1/auth/password', [
                'password_actual'       => 'no-es-esta',
                'password'              => 'NuevaClave2026',
                'password_confirmation' => 'NuevaClave2026',
            ])
            ->assertStatus(422)
            ->assertJsonPath('codigo', 'VALIDACION')
            ->assertJsonStructure(['errores' => ['password_actual']]);

        $this->assertTrue($op->refresh()->debe_cambiar_password);
    }
}

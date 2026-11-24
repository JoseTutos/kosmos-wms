<?php

namespace Tests\Feature\Api\V1;

use App\Enums\Rol;
use App\Http\Middleware\Idempotencia as IdempotenciaMiddleware;
use App\Models\Idempotencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Concerns\CreaUsuarios;
use Tests\TestCase;

/** D13: cada operación se procesa una sola vez por Idempotency-Key. */
final class IdempotenciaTest extends TestCase
{
    use CreaUsuarios, RefreshDatabase;

    private int $ejecuciones = 0;

    private User $operario;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'auth:sanctum', 'idempotencia'])->prefix('api/v1/_prueba')->group(function () {
            Route::post('eco', function (Request $r) {
                $this->ejecuciones++;

                return response()->json(['data' => ['n' => $this->ejecuciones, 'valor' => $r->input('valor')]], 201);
            });
            Route::post('falla', function () {
                $this->ejecuciones++;
                abort(500);
            });
            Route::put('editar', function () {
                $this->ejecuciones++;

                return response()->json(['data' => ['ok' => true]]);
            });
        });

        $this->operario = $this->crearUsuario(Rol::OPERARIO);
        $this->token = $this->tokenPara($this->operario);
    }

    private function enviar(string $ruta, array $datos = [], ?string $clave = null)
    {
        $peticion = $this->withToken($this->token);

        if ($clave !== null) {
            $peticion = $peticion->withHeader('Idempotency-Key', $clave);
        }

        return $peticion->postJson("/api/v1/_prueba/{$ruta}", $datos);
    }

    public function test_el_reintento_devuelve_la_misma_respuesta_sin_reprocesar(): void
    {
        $clave = (string) Str::uuid();

        $primera = $this->enviar('eco', ['valor' => 'A'], $clave)->assertCreated();
        $segunda = $this->enviar('eco', ['valor' => 'A'], $clave)->assertCreated()->assertHeader('Idempotency-Replayed', 'true');

        $this->assertSame(1, $this->ejecuciones);
        $this->assertSame($primera->json(), $segunda->json());
    }

    public function test_el_orden_de_los_campos_no_cambia_la_huella(): void
    {
        $clave = (string) Str::uuid();

        $this->enviar('eco', ['valor' => 'A', 'otro' => 1], $clave)->assertCreated();
        $this->enviar('eco', ['otro' => 1, 'valor' => 'A'], $clave)->assertCreated()->assertHeader('Idempotency-Replayed', 'true');

        $this->assertSame(1, $this->ejecuciones);
    }

    public function test_post_sin_clave_responde_428(): void
    {
        $this->enviar('eco')->assertStatus(428)->assertJsonPath('codigo', 'IDEMPOTENCIA_CLAVE_REQUERIDA');
        $this->assertSame(0, $this->ejecuciones);
    }

    public function test_clave_que_no_es_uuid_responde_400(): void
    {
        $this->enviar('eco', [], 'abc-123')->assertStatus(400)->assertJsonPath('codigo', 'IDEMPOTENCIA_CLAVE_INVALIDA');
        $this->assertSame(0, $this->ejecuciones);
    }

    public function test_misma_clave_con_otro_cuerpo_responde_422(): void
    {
        $clave = (string) Str::uuid();

        $this->enviar('eco', ['valor' => 'A'], $clave)->assertCreated();
        $this->enviar('eco', ['valor' => 'B'], $clave)->assertStatus(422)->assertJsonPath('codigo', 'IDEMPOTENCIA_CLAVE_REUTILIZADA');

        $this->assertSame(1, $this->ejecuciones);
    }

    public function test_un_error_500_permite_reprocesar(): void
    {
        $clave = (string) Str::uuid();

        $this->enviar('falla', [], $clave)->assertStatus(500);
        $this->assertSame(0, Idempotencia::count());

        $this->enviar('falla', [], $clave)->assertStatus(500);
        $this->assertSame(2, $this->ejecuciones);
    }

    public function test_la_clave_es_por_usuario(): void
    {
        $clave = (string) Str::uuid();
        $this->enviar('eco', ['valor' => 'A'], $clave)->assertCreated();

        $otro = $this->crearUsuario(Rol::OPERARIO);
        $this->olvidarAutenticacion();
        $this->withToken($this->tokenPara($otro))->withHeader('Idempotency-Key', $clave)
            ->postJson('/api/v1/_prueba/eco', ['valor' => 'A'])
            ->assertCreated()
            ->assertHeaderMissing('Idempotency-Replayed');

        $this->assertSame(2, $this->ejecuciones);
    }

    public function test_clave_en_proceso_responde_409_y_abandonada_se_reprocesa(): void
    {
        $clave = (string) Str::uuid();
        $huella = IdempotenciaMiddleware::huella('POST', 'api/v1/_prueba/eco', ['valor' => 'A']);

        DB::table('idempotencia')->insert([
            'usuario_id' => $this->operario->id, 'clave' => $clave, 'metodo' => 'POST',
            'ruta' => 'api/v1/_prueba/eco', 'huella' => $huella,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->enviar('eco', ['valor' => 'A'], $clave)->assertStatus(409)->assertJsonPath('codigo', 'IDEMPOTENCIA_EN_PROCESO');
        $this->assertSame(0, $this->ejecuciones);

        // El proceso original murió hace más del límite de abandono.
        DB::table('idempotencia')->where('clave', $clave)->update(['updated_at' => now()->subMinutes(5)]);

        $this->enviar('eco', ['valor' => 'A'], $clave)->assertCreated();
        $this->assertSame(1, $this->ejecuciones);
        $this->assertTrue(Idempotencia::where('clave', $clave)->first()->completada());
    }

    public function test_put_sin_clave_pasa_y_con_clave_se_controla(): void
    {
        $this->withToken($this->token)->putJson('/api/v1/_prueba/editar')->assertOk();

        $clave = (string) Str::uuid();
        $this->withToken($this->token)->withHeader('Idempotency-Key', $clave)->putJson('/api/v1/_prueba/editar')->assertOk();
        $this->withToken($this->token)->withHeader('Idempotency-Key', $clave)->putJson('/api/v1/_prueba/editar')
            ->assertOk()->assertHeader('Idempotency-Replayed', 'true');

        $this->assertSame(2, $this->ejecuciones);
    }

    public function test_las_claves_vencidas_se_purgan(): void
    {
        $this->enviar('eco', ['valor' => 'A'], (string) Str::uuid())->assertCreated();
        DB::table('idempotencia')->update(['created_at' => now()->subHours(25)]);
        $this->enviar('eco', ['valor' => 'B'], (string) Str::uuid())->assertCreated();

        $this->artisan('model:prune', ['--model' => [Idempotencia::class]])->assertSuccessful();

        $this->assertSame(1, Idempotencia::count());
    }
}

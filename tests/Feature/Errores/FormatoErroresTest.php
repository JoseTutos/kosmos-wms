<?php

namespace Tests\Feature\Errores;

use App\Enums\CodigoError;
use App\Exceptions\ReglaNegocioException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Conversión de ReglaNegocioException y contrato de errores de /api/v1. */
final class FormatoErroresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $lanzar = fn () => throw new ReglaNegocioException(
            CodigoError::PALLET_CERRADO,
            datos: ['pallet' => 'PLT-231126-0001'],
            campo: 'pallet',
        );

        Route::middleware('api')->post('api/v1/_prueba/regla', $lanzar);
        Route::middleware('web')->post('_prueba/regla', $lanzar);
    }

    public function test_regla_de_negocio_en_la_api_es_422_json(): void
    {
        $this->post('/api/v1/_prueba/regla') // sin Accept: application/json
            ->assertStatus(422)
            ->assertExactJson([
                'mensaje' => CodigoError::PALLET_CERRADO->mensaje(),
                'codigo'  => 'PALLET_CERRADO',
                'datos'   => ['pallet' => 'PLT-231126-0001'],
            ]);
    }

    public function test_regla_de_negocio_en_la_web_vuelve_con_el_error(): void
    {
        $this->from('/origen')
            ->post('/_prueba/regla', ['nombre' => 'X', 'password' => 'secreta'])
            ->assertRedirect('/origen')
            ->assertSessionHasErrors(['pallet' => CodigoError::PALLET_CERRADO->mensaje()])
            ->assertSessionHasInput('nombre', 'X')
            ->assertSessionMissing('_old_input.password');
    }

    public function test_ruta_inexistente_de_la_api_es_404_json(): void
    {
        $this->get('/api/v1/no-existe')
            ->assertStatus(404)
            ->assertJsonPath('codigo', 'NO_ENCONTRADO')
            ->assertJsonPath('mensaje', CodigoError::NO_ENCONTRADO->mensaje());
    }

    public function test_metodo_no_permitido_es_405_json(): void
    {
        $this->getJson('/api/v1/auth/login')
            ->assertStatus(405)
            ->assertJsonPath('codigo', 'METODO_NO_PERMITIDO');
    }
}

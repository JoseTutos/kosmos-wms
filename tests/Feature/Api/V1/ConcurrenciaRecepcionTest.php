<?php

namespace Tests\Feature\Api\V1;

use App\Enums\Rol;
use App\Models\Bl;
use App\Models\Bulto;
use App\Models\Movimiento;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * D8 / R8: dos operarios reciben el mismo BL a la vez.
 * PHPUnit corre en un solo proceso, así que se intercalan las peticiones: se verifica que los consecutivos
 * no se repitan, que todo cuente, que un reintento no duplique (D13) y que tras finalizar se rechacen bultos.
 * El bloqueo real (FOR SHARE frente al FOR UPDATE de finalizar) lo garantiza PostgreSQL.
 */
final class ConcurrenciaRecepcionTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase, UsaApi;

    public function test_dos_operarios_en_el_mismo_bl(): void
    {
        $ana = $this->crearUsuario(Rol::OPERARIO);
        $luis = $this->crearUsuario(Rol::OPERARIO);
        $cliente = $this->crearCliente();
        $producto = $this->crearProducto($cliente);
        $bl = $this->crearBl($cliente, $producto);   // 10 bultos esperados

        $this->apiPost($ana, "/api/v1/recepciones/{$bl->id}/iniciar")->assertOk();
        $this->apiPost($luis, "/api/v1/recepciones/{$bl->id}/iniciar")->assertOk();   // se une sin error

        $palletAna = $this->apiPost($ana, "/api/v1/recepciones/{$bl->id}/pallets")->json('data.pallet.codigo');
        $palletLuis = $this->apiPost($luis, "/api/v1/recepciones/{$bl->id}/pallets")->json('data.pallet.codigo');
        $this->assertNotSame($palletAna, $palletLuis);

        $codigos = [];
        for ($i = 0; $i < 5; $i++) {
            foreach ([[$ana, $palletAna], [$luis, $palletLuis]] as [$operario, $pallet]) {
                $codigos[] = $this->registrar($operario, $bl, $producto, $pallet)->assertCreated()->json('data.bultos.0.codigo');
            }
        }

        $this->assertCount(10, array_unique($codigos));                         // consecutivos sin repetir

        // D13: la app reintenta (mala red) con la misma clave → no se duplica
        $clave = (string) Str::uuid();
        $primero = $this->registrar($ana, $bl, $producto, $palletAna, $clave)->json('data.bultos.0.codigo');
        $reintento = $this->registrar($ana, $bl, $producto, $palletAna, $clave)->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame($primero, $reintento->json('data.bultos.0.codigo'));
        $this->assertSame(11, Bulto::where('bl_id', $bl->id)->count());

        // Luis finaliza (sobra 1 bulto → observación); Ana llega tarde
        $this->apiPost($luis, "/api/v1/recepciones/{$bl->id}/finalizar", ['observacion' => 'Llegó un bulto de más.'])
            ->assertOk()
            ->assertJsonPath('data.bultos.recibidos', 11)
            ->assertJsonPath('data.bultos.diferencia', 1);

        $this->registrar($ana, $bl, $producto, $palletAna)
            ->assertStatus(422)->assertJsonPath('codigo', 'BL_NO_EN_RECEPCION');

        $this->assertSame(11, Movimiento::count());
        $this->assertSame(1, $bl->refresh()->discrepancia_bultos);
    }

    private function registrar($operario, Bl $bl, Producto $producto, string $pallet, ?string $clave = null)
    {
        return $this->apiPost($operario, "/api/v1/recepciones/{$bl->id}/bultos", [
            'peso_bruto_kg' => '50',
            'lineas'        => [['producto_id' => $producto->id, 'cantidad' => '10']],
            'pallet'        => $pallet,
        ], $clave);
    }
}

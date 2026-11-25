<?php

namespace Tests\Feature\Api\V1;

use App\Enums\Rol;
use App\Models\Serial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** D14: un endpoint identifica cualquier etiqueta escaneada. */
final class EscaneoTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase, UsaApi;

    public function test_identifica_cada_tipo_de_etiqueta(): void
    {
        $operario = $this->crearUsuario(Rol::OPERARIO);
        $cliente = $this->crearCliente();
        $producto = $this->crearProducto($cliente);
        $bl = $this->crearBl($cliente, $producto);
        $pallet = $this->crearPallet($bl);
        $bulto = $this->crearBulto($bl, $producto);
        $bodega = $this->crearBodega(true);
        $ubicacion = $this->crearUbicacion($bodega);
        $serial = Serial::create(['codigo' => "{$producto->sku}-0001", 'bulto_detalle_id' => $bulto->detalles()->first()->id]);

        $casos = [
            $pallet->codigo                          => 'pallet',
            strtolower($bulto->codigo)               => 'bulto',
            $serial->codigo                          => 'serial',
            "{$bodega->codigo}/{$ubicacion->codigo}" => 'ubicacion',
            $bodega->codigo                          => 'bodega',
        ];

        foreach ($casos as $codigo => $tipo) {
            $this->apiGet($operario, '/api/v1/escaneos/'.$codigo)
                ->assertOk()
                ->assertJsonPath('data.tipo', $tipo);
        }

        $this->apiGet($operario, "/api/v1/escaneos/{$bulto->codigo}")
            ->assertJsonPath('data.datos.lineas.0.sku', $producto->sku);

        $this->apiGet($operario, '/api/v1/escaneos/NO-EXISTE')
            ->assertStatus(422)
            ->assertJsonPath('codigo', 'CODIGO_NO_ENCONTRADO');
    }
}

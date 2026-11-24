<?php

namespace Tests\Feature\Web;

use App\Enums\Rol;
use App\Models\Aforo;
use App\Models\Bl;
use App\Models\Bodega;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** CUS-009 web del Supervisor: activar, seguir, cerrar y descargar el reporte. */
final class AforoTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase, UsaApi;

    private User $supervisor;

    private User $operario;

    private Bl $bl;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->supervisor = $this->crearUsuario(Rol::SUPERVISOR);
        $this->operario = $this->crearUsuario(Rol::OPERARIO);
        $cliente = $this->crearCliente();
        $producto = $this->crearProducto($cliente);
        $bodega = $this->crearBodega();

        $this->bl = $this->crearBl($cliente, $producto);
        $this->bl->iniciarRecepcion();
        $this->bl->save();
        $pallet = $this->crearPallet($this->bl);
        $bulto = $this->crearBulto($this->bl, $producto, '10');
        $pallet->agregarBulto($bulto);
        $bulto->save();
        $pallet->cerrar();
        $pallet->save();

        $this->apiPost($this->operario, '/api/v1/almacenamiento/ubicar', ['pallet' => $pallet->codigo, 'bodega' => $bodega->codigo, 'escaneo' => $bodega->codigo])->assertOk();
        $this->apiPost($this->operario, "/api/v1/recepciones/{$this->bl->id}/finalizar", ['observacion' => 'Prueba.'])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
    }

    public function test_flujo_completo_del_supervisor(): void
    {
        $this->actingAs($this->supervisor)->get(route('aforos.create', ['bl' => $this->bl->id]))
            ->assertOk()
            ->assertSee($this->bl->numero_bl);

        $bultos = $this->bl->bultos()->pluck('id')->all();

        $respuesta = $this->actingAs($this->supervisor)->post(route('aforos.store'), ['bl_id' => $this->bl->id, 'bultos' => $bultos]);
        $aforo = Aforo::firstOrFail();
        $respuesta->assertRedirect(route('aforos.show', $aforo));

        $this->actingAs($this->supervisor)->get(route('aforos.show', $aforo))->assertOk()->assertSee('Pendiente');
        $this->actingAs($this->supervisor)->get(route('aforos.index'))->assertOk()->assertSee("AFO-{$aforo->id}");

        // Cerrar sin contar: vuelve con el error del dominio
        $this->actingAs($this->supervisor)->from(route('aforos.show', $aforo))
            ->patch(route('aforos.cerrar', $aforo))
            ->assertRedirect(route('aforos.show', $aforo))
            ->assertSessionHasErrors('negocio');

        $this->apiPost($this->operario, "/api/v1/aforos/{$aforo->id}/conteos", ['detalle_id' => $aforo->detalles()->value('id'), 'cantidad' => '12'])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        $this->actingAs($this->supervisor)->patch(route('aforos.cerrar', $aforo))->assertRedirect(route('aforos.show', $aforo));
        $this->assertSame('CERRADO', $aforo->refresh()->estado->value);

        $this->actingAs($this->supervisor)->get(route('aforos.show', $aforo))->assertOk()->assertSee('+2.000');
        $this->actingAs($this->supervisor)->get(route('aforos.reporte', [$aforo, 'pdf']))->assertOk()->assertDownload("diferencias-aforo-{$aforo->id}.pdf");
        $this->actingAs($this->supervisor)->get(route('aforos.reporte', [$aforo, 'xlsx']))->assertOk()->assertDownload("diferencias-aforo-{$aforo->id}.xlsx");
    }

    public function test_activar_sin_bultos_se_rechaza(): void
    {
        $this->actingAs($this->supervisor)->from(route('aforos.create', ['bl' => $this->bl->id]))
            ->post(route('aforos.store'), ['bl_id' => $this->bl->id, 'bultos' => []])
            ->assertSessionHasErrors('bultos');

        $this->assertSame(0, Aforo::count());
    }
}

<?php

namespace Tests\Unit\Nucleo;

use App\Enums\CodigoError;
use App\Enums\EstadoLegal;
use App\Enums\Rol;
use App\Enums\TipoMovimiento;
use App\Models\Bodega;
use App\Models\BultoDetalle;
use App\Models\Existencia;
use App\Models\Movimiento;
use App\Models\User;
use App\Services\Nucleo\MovimientoService;
use App\Support\Milesimas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

final class MovimientoInventarioTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    private MovimientoService $movimientos;

    private User $operario;

    private BultoDetalle $linea;

    private Bodega $a;

    private Bodega $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->movimientos = app(MovimientoService::class);
        $this->operario = $this->crearUsuario(Rol::OPERARIO);

        $cliente = $this->crearCliente();
        $producto = $this->crearProducto($cliente);
        $bl = $this->crearBl($cliente, $producto);
        $this->linea = $this->crearBulto($bl, $producto, '10')->detalles()->firstOrFail();

        $this->a = $this->crearBodega();
        $this->b = $this->crearBodega();
    }

    private function recibir(?int $bodegaId, string $cantidad = '10'): Movimiento
    {
        return DB::transaction(fn () => $this->movimientos->registrar(
            TipoMovimiento::RECEPCION, $this->linea, $cantidad, $bodegaId, EstadoLegal::CUARENTENA, $this->operario, 'BL-PRUEBA',
        ));
    }

    private function cuarentena(Bodega $bodega): int
    {
        return Milesimas::de(Existencia::where('bodega_id', $bodega->id)->value('cantidad_cuarentena'));
    }

    private function saldoKardex(): int
    {
        return Milesimas::de((string) Movimiento::where('bulto_detalle_id', $this->linea->id)->sum('cantidad'));
    }

    public function test_recepcion_en_bodega_suma_cuarentena(): void
    {
        $movimiento = $this->recibir($this->a->id);

        $this->assertSame('10.000', $movimiento->cantidad);
        $this->assertSame($this->operario->id, $movimiento->usuario_id);
        $this->assertSame(10000, $this->cuarentena($this->a));
    }

    public function test_recepcion_sin_bodega_solo_queda_en_el_kardex(): void
    {
        $this->recibir(null);

        $this->assertSame(1, Movimiento::count());
        $this->assertSame(0, Existencia::count());
    }

    public function test_primera_ubicacion_y_traslado_conservan_el_saldo_del_kardex(): void
    {
        $this->recibir(null);

        DB::transaction(function () {
            $this->movimientos->trasladar($this->linea, null, $this->a->id, '10', EstadoLegal::CUARENTENA, $this->operario);
            $this->movimientos->trasladar($this->linea, $this->a->id, $this->b->id, '4', EstadoLegal::CUARENTENA, $this->operario);
        });

        $this->assertSame(10000, $this->saldoKardex());
        $this->assertSame(6000, $this->cuarentena($this->a));
        $this->assertSame(4000, $this->cuarentena($this->b));
    }

    public function test_salida_mayor_a_la_existencia_no_deja_rastro(): void
    {
        $this->recibir($this->a->id);

        $this->assertRegla(CodigoError::EXISTENCIA_INSUFICIENTE, fn () => DB::transaction(
            fn () => $this->movimientos->trasladar($this->linea, $this->a->id, $this->b->id, '11', EstadoLegal::CUARENTENA, $this->operario)
        ));

        $this->assertSame(1, Movimiento::count());
        $this->assertSame(10000, $this->cuarentena($this->a));
        $this->assertSame(0, Existencia::where('bodega_id', $this->b->id)->count());
    }

    public function test_cantidad_cero_y_traslado_a_la_misma_bodega_se_rechazan(): void
    {
        $this->assertRegla(CodigoError::CANTIDAD_INVALIDA, fn () => $this->recibir($this->a->id, '0'));
        $this->assertRegla(CodigoError::TRASLADO_MISMA_BODEGA, fn () => DB::transaction(
            fn () => $this->movimientos->trasladar($this->linea, $this->a->id, $this->a->id, '1', EstadoLegal::CUARENTENA, $this->operario)
        ));
    }

    public function test_el_movimiento_es_de_solo_insercion(): void
    {
        $movimiento = $this->recibir($this->a->id);

        $this->expectException(LogicException::class);
        $movimiento->documento_ref = 'OTRO';
        $movimiento->save();
    }
}

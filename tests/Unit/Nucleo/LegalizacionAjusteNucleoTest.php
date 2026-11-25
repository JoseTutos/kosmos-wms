<?php

namespace Tests\Unit\Nucleo;

use App\Enums\CodigoError;
use App\Enums\EstadoLegal;
use App\Enums\MotivoAjuste;
use App\Enums\Rol;
use App\Enums\TipoAjuste;
use App\Enums\TipoDocumentoAutorizacion;
use App\Enums\TipoMovimiento;
use App\Models\Ajuste;
use App\Models\Bl;
use App\Models\Bodega;
use App\Models\BultoDetalle;
use App\Models\Existencia;
use App\Models\Legalizacion;
use App\Models\LegalizacionDetalle;
use App\Models\LegalizacionSerial;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\Serial;
use App\Models\User;
use App\Services\Nucleo\ConsecutivoService;
use App\Services\Nucleo\MovimientoService;
use App\Support\Milesimas;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

/** Módulo 3 · Núcleo: LEGALIZACION (L17), AJUSTE (J8), consecutivos LEG/AJ (L15, J8) e inmutabilidad (L18, L19). */
final class LegalizacionAjusteNucleoTest extends TestCase
{
    use CreaEscenarioInbound, RefreshDatabase;

    private MovimientoService $movimientos;

    private ConsecutivoService $consecutivos;

    private User $usuario;

    private Producto $producto;

    private Bl $bl;

    private BultoDetalle $linea;

    private Bodega $bodega;

    protected function setUp(): void
    {
        parent::setUp();

        $this->movimientos = app(MovimientoService::class);
        $this->consecutivos = app(ConsecutivoService::class);
        $this->usuario = $this->crearUsuario(Rol::SUPERVISOR);

        $cliente = $this->crearCliente();
        $this->producto = $this->crearProducto($cliente);
        $this->bl = $this->crearBl($cliente, $this->producto);
        $this->linea = $this->crearBulto($this->bl, $this->producto, '10')->detalles()->firstOrFail();
        $this->bodega = $this->crearBodega();

        DB::transaction(fn () => $this->movimientos->registrar(
            TipoMovimiento::RECEPCION, $this->linea, '10', $this->bodega->id, EstadoLegal::CUARENTENA, $this->usuario, 'BL-PRUEBA',
        ));
    }

    private function legalizar(string $cantidad): void
    {
        DB::transaction(function () use ($cantidad) {
            $q = $this->linea->legalizarPorCantidad($cantidad);
            $this->linea->save();
            $this->movimientos->legalizar($this->linea, $q, $this->bodega->id, $this->usuario, 'LEG-2026-00001');
        });
    }

    private function ajustar(TipoAjuste $tipo, string $cantidad, EstadoLegal $estado): Movimiento
    {
        return DB::transaction(function () use ($tipo, $cantidad, $estado) {
            $this->linea->ajustar($tipo, $cantidad, $estado);
            $this->linea->save();

            return $this->movimientos->ajustar($this->linea, $tipo, $cantidad, $this->bodega->id, $estado, $this->usuario, 'AJ-2026-00001');
        });
    }

    private function existencia(): Existencia
    {
        return Existencia::where('bodega_id', $this->bodega->id)->firstOrFail();
    }

    /** El saldo del kardex por línea debe ser siempre igual a la cantidad recibida. */
    private function assertKardexIgualARecibida(): void
    {
        $saldo = Milesimas::de((string) Movimiento::where('bulto_detalle_id', $this->linea->id)->sum('cantidad'));

        $this->assertSame(Milesimas::de($this->linea->fresh()->cantidad_recibida), $saldo);
    }

    // ---------------- LEGALIZACION ----------------

    public function test_legalizar_registra_dos_filas_y_pasa_la_existencia_a_legalizada(): void
    {
        $this->legalizar('4');

        $filas = Movimiento::where('tipo', TipoMovimiento::LEGALIZACION->value)->orderBy('id')->get();

        $this->assertCount(2, $filas);
        $this->assertSame(['-4.000', '4.000'], $filas->pluck('cantidad')->all());
        $this->assertSame([EstadoLegal::CUARENTENA, EstadoLegal::LEGALIZADA], $filas->pluck('estado_legal')->all());
        $this->assertSame(['LEG-2026-00001'], $filas->pluck('documento_ref')->unique()->values()->all());
        $this->assertSame([$this->bodega->id], $filas->pluck('bodega_id')->unique()->values()->all());

        $this->assertSame('6.000', $this->existencia()->cantidad_cuarentena);
        $this->assertSame('4.000', $this->existencia()->cantidad_legalizada);
        $this->assertSame('4.000', $this->linea->fresh()->cantidad_legalizada);
        $this->assertKardexIgualARecibida();
    }

    public function test_legalizar_sin_existencia_suficiente_no_deja_rastro(): void
    {
        $this->assertRegla(CodigoError::EXISTENCIA_INSUFICIENTE, fn () => DB::transaction(
            fn () => $this->movimientos->legalizar($this->linea, '11', $this->bodega->id, $this->usuario, 'LEG-2026-00001')
        ));
        $this->assertRegla(CodigoError::CANTIDAD_INVALIDA, fn () => DB::transaction(
            fn () => $this->movimientos->legalizar($this->linea, '0', $this->bodega->id, $this->usuario)
        ));

        $this->assertSame(0, Movimiento::where('tipo', TipoMovimiento::LEGALIZACION->value)->count());
        $this->assertSame('10.000', $this->existencia()->cantidad_cuarentena);
        $this->assertSame('0.000', $this->existencia()->cantidad_legalizada);
    }

    // ---------------- AJUSTE ----------------

    public function test_ajuste_positivo_en_cuarentena(): void
    {
        $movimiento = $this->ajustar(TipoAjuste::POSITIVO, '2', EstadoLegal::CUARENTENA);

        $this->assertSame(TipoMovimiento::AJUSTE, $movimiento->tipo);
        $this->assertSame('2.000', $movimiento->cantidad);
        $this->assertSame(EstadoLegal::CUARENTENA, $movimiento->estado_legal);
        $this->assertSame('12.000', $this->existencia()->cantidad_cuarentena);
        $this->assertKardexIgualARecibida();
    }

    public function test_ajuste_negativo_de_legalizadas(): void
    {
        $this->legalizar('4');

        $movimiento = $this->ajustar(TipoAjuste::NEGATIVO, '1', EstadoLegal::LEGALIZADA);

        $this->assertSame('-1.000', $movimiento->cantidad);
        $this->assertSame(EstadoLegal::LEGALIZADA, $movimiento->estado_legal);
        $this->assertSame('6.000', $this->existencia()->cantidad_cuarentena);
        $this->assertSame('3.000', $this->existencia()->cantidad_legalizada);
        $this->assertSame('9.000', $this->linea->fresh()->cantidad_recibida);
        $this->assertKardexIgualARecibida();
    }

    public function test_la_cantidad_del_ajuste_va_sin_signo(): void
    {
        $this->assertRegla(CodigoError::CANTIDAD_INVALIDA, fn () => DB::transaction(
            fn () => $this->movimientos->ajustar($this->linea, TipoAjuste::NEGATIVO, '-2', $this->bodega->id, EstadoLegal::CUARENTENA, $this->usuario)
        ));

        $this->assertSame(0, Movimiento::where('tipo', TipoMovimiento::AJUSTE->value)->count());
    }

    // ---------------- Consecutivos LEG / AJ ----------------

    public function test_consecutivos_leg_y_aj_son_anuales_en_la_zona_de_la_operacion(): void
    {
        $marzo = CarbonImmutable::create(2026, 3, 1, 12, 0, 0, 'America/Bogota');

        $this->assertSame('LEG-2026-00001', $this->consecutivos->legalizacion($marzo));
        $this->assertSame('LEG-2026-00002', $this->consecutivos->legalizacion($marzo));
        $this->assertSame('LEG-2027-00001', $this->consecutivos->legalizacion($marzo->addYear()));
        // 1 de enero 03:00 UTC = 31 de diciembre 22:00 en Bogotá: sigue siendo 2026.
        $this->assertSame('LEG-2026-00003', $this->consecutivos->legalizacion(CarbonImmutable::parse('2027-01-01 03:00:00', 'UTC')));

        $this->assertSame('AJ-2026-00001', $this->consecutivos->ajuste($marzo));
        $this->assertSame('AJ-2026-00002', $this->consecutivos->ajuste($marzo));
    }

    // ---------------- Inmutabilidad y relaciones ----------------

    public function test_los_registros_del_modulo_son_de_solo_insercion(): void
    {
        $legalizacion = Legalizacion::create([
            'consecutivo'         => $this->consecutivos->legalizacion(),
            'bl_id'               => $this->bl->id,
            'tipo_documento'      => TipoDocumentoAutorizacion::VOBO,
            'numero_autorizacion' => 'VB-2026-123',
            'fecha_autorizacion'  => '2026-11-20',
            'soporte_path'        => 'legalizaciones/soporte.pdf',
            'usuario_id'          => $this->usuario->id,
        ]);
        $detalle = LegalizacionDetalle::create([
            'legalizacion_id' => $legalizacion->id, 'bulto_detalle_id' => $this->linea->id, 'cantidad' => '1',
        ]);
        $serial = Serial::create(['codigo' => $this->consecutivos->serial($this->producto->sku), 'bulto_detalle_id' => $this->linea->id]);
        $legalizacionSerial = LegalizacionSerial::create(['legalizacion_detalle_id' => $detalle->id, 'serial_id' => $serial->id]);
        $ajuste = Ajuste::create([
            'consecutivo'      => $this->consecutivos->ajuste(),
            'bulto_detalle_id' => $this->linea->id,
            'tipo'             => TipoAjuste::POSITIVO,
            'estado_afectado'  => EstadoLegal::CUARENTENA,
            'cantidad'         => '1',
            'motivo'           => MotivoAjuste::OTRO,
            'descripcion'      => 'Prueba de inmutabilidad',
            'usuario_id'       => $this->usuario->id,
        ]);

        $this->assertSoloInsercion($legalizacion, 'numero_autorizacion', 'OTRO-NUMERO');
        $this->assertSoloInsercion($detalle, 'cantidad', '2');
        $this->assertSoloInsercion($legalizacionSerial, 'serial_id', $serial->id + 1);
        $this->assertSoloInsercion($ajuste, 'descripcion', 'Cambio');

        // Relaciones e inversas
        $this->assertTrue($this->bl->legalizaciones()->whereKey($legalizacion->id)->exists());
        $this->assertSame(1, $this->linea->legalizacionDetalles()->count());
        $this->assertSame(1, $this->linea->ajustes()->count());
        $this->assertSame($serial->id, $legalizacion->seriales()->value('serial_id'));
        $this->assertSame($detalle->id, $serial->legalizacionSerial->legalizacion_detalle_id);
        $this->assertSame(1, $this->usuario->legalizaciones()->count());
        $this->assertSame(1, $this->usuario->ajustes()->count());
        $this->assertSame(TipoDocumentoAutorizacion::VOBO, $legalizacion->fresh()->tipo_documento);
        $this->assertNull($ajuste->fresh()->soporte_path);
    }

    private function assertSoloInsercion(Model $registro, string $campo, mixed $valor): void
    {
        $nombre = class_basename($registro);

        try {
            $registro->{$campo} = $valor;
            $registro->save();
            $this->fail("{$nombre} permitió una edición.");
        } catch (LogicException) {
            $registro->refresh();
        }

        try {
            $registro->delete();
            $this->fail("{$nombre} permitió un borrado.");
        } catch (LogicException) {
        }

        $this->assertTrue($registro->newQuery()->whereKey($registro->getKey())->exists(), "{$nombre} desapareció.");
    }
}

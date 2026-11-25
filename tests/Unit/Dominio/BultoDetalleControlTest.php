<?php

namespace Tests\Unit\Dominio;

use App\Enums\CodigoError;
use App\Enums\EstadoLegal;
use App\Enums\EstadoLegalSerial;
use App\Enums\TipoAjuste;
use App\Models\BultoDetalle;
use App\Models\Producto;
use App\Models\Serial;
use Tests\Concerns\CreaEscenarioInbound;
use Tests\TestCase;

/** Módulo 3 · reglas de cantidad de la línea (L4–L6, L9, J2, J3). Sin base de datos. */
final class BultoDetalleControlTest extends TestCase
{
    use CreaEscenarioInbound;

    private function linea(
        string $recibida,
        string $legalizada = '0',
        string $reservada = '0',
        string $despachada = '0',
        string $unidad = 'UND',
        bool $serializado = false,
    ): BultoDetalle {
        $linea = (new BultoDetalle)->forceFill([
            'id'                  => 1,
            'producto_id'         => 1,
            'cantidad_recibida'   => $recibida,
            'cantidad_legalizada' => $legalizada,
            'cantidad_reservada'  => $reservada,
            'cantidad_despachada' => $despachada,
            'serializado'         => $serializado,
        ]);

        return $linea->setRelation('producto', (new Producto)->forceFill(['unidad_medida' => $unidad]));
    }

    private function serial(string $codigo, int $lineaId = 1, EstadoLegalSerial $estado = EstadoLegalSerial::EN_CUARENTENA): Serial
    {
        return (new Serial)->forceFill(['codigo' => $codigo, 'bulto_detalle_id' => $lineaId, 'estado_legal' => $estado]);
    }

    // ---------------- ajustar (CUS-019) ----------------

    public function test_ajuste_positivo_en_cuarentena_solo_mueve_la_recibida(): void
    {
        $linea = $this->linea('10', '4');

        $this->assertSame('2.000', $linea->ajustar(TipoAjuste::POSITIVO, '2', EstadoLegal::CUARENTENA));
        $this->assertSame('12.000', $linea->cantidad_recibida);
        $this->assertSame('4.000', $linea->cantidad_legalizada);
    }

    public function test_ajuste_positivo_en_legalizada_mueve_recibida_y_legalizada(): void
    {
        $linea = $this->linea('10', '4');

        $linea->ajustar(TipoAjuste::POSITIVO, '2', EstadoLegal::LEGALIZADA);

        $this->assertSame('12.000', $linea->cantidad_recibida);
        $this->assertSame('6.000', $linea->cantidad_legalizada);
    }

    public function test_ajuste_negativo_en_cuarentena_no_supera_la_cuarentena(): void
    {
        $linea = $this->linea('10', '4');

        $this->assertSame('-6.000', $linea->ajustar(TipoAjuste::NEGATIVO, '6', EstadoLegal::CUARENTENA));
        $this->assertSame('4.000', $linea->cantidad_recibida);
        $this->assertSame('4.000', $linea->cantidad_legalizada);

        $this->assertRegla(CodigoError::AJUSTE_DEJA_NEGATIVO,
            fn () => $this->linea('10', '4')->ajustar(TipoAjuste::NEGATIVO, '7', EstadoLegal::CUARENTENA));
    }

    public function test_ajuste_negativo_en_legalizada_respeta_reservadas_y_despachadas(): void
    {
        $linea = $this->linea('10', '6', '2', '1');

        $this->assertSame('-3.000', $linea->ajustar(TipoAjuste::NEGATIVO, '3', EstadoLegal::LEGALIZADA));
        $this->assertSame('7.000', $linea->cantidad_recibida);
        $this->assertSame('3.000', $linea->cantidad_legalizada);

        $this->assertRegla(CodigoError::AJUSTE_AFECTA_RESERVADAS,
            fn () => $this->linea('10', '6', '2', '1')->ajustar(TipoAjuste::NEGATIVO, '4', EstadoLegal::LEGALIZADA));
        $this->assertRegla(CodigoError::AJUSTE_DEJA_NEGATIVO,
            fn () => $this->linea('10', '6')->ajustar(TipoAjuste::NEGATIVO, '7', EstadoLegal::LEGALIZADA));
    }

    public function test_ajuste_valida_cantidad_y_unidad(): void
    {
        $this->assertRegla(CodigoError::CANTIDAD_INVALIDA,
            fn () => $this->linea('10')->ajustar(TipoAjuste::POSITIVO, '0', EstadoLegal::CUARENTENA));
        $this->assertRegla(CodigoError::CANTIDAD_NO_ENTERA,
            fn () => $this->linea('10')->ajustar(TipoAjuste::POSITIVO, '1.5', EstadoLegal::CUARENTENA));

        $kilos = $this->linea('10', unidad: 'KG');
        $kilos->ajustar(TipoAjuste::NEGATIVO, '1.255', EstadoLegal::CUARENTENA);
        $this->assertSame('8.745', $kilos->cantidad_recibida);
    }

    public function test_un_ajuste_rechazado_no_cambia_la_linea(): void
    {
        $linea = $this->linea('10', '6', '5');

        $this->assertRegla(CodigoError::AJUSTE_AFECTA_RESERVADAS,
            fn () => $linea->ajustar(TipoAjuste::NEGATIVO, '2', EstadoLegal::LEGALIZADA));
        $this->assertSame('10.000', $linea->cantidad_recibida);
        $this->assertSame('6.000', $linea->cantidad_legalizada);
    }

    // ---------------- legalizar por cantidad (Modo B) ----------------

    public function test_legalizar_por_cantidad(): void
    {
        $linea = $this->linea('10', '4');

        $this->assertSame('3.000', $linea->legalizarPorCantidad('3'));
        $this->assertSame('7.000', $linea->cantidad_legalizada);
        $this->assertSame('3.000', $linea->en_cuarentena);

        $this->assertRegla(CodigoError::CANTIDAD_SUPERA_CUARENTENA, fn () => $linea->legalizarPorCantidad('4'));
        $this->assertRegla(CodigoError::CANTIDAD_NO_ENTERA, fn () => $linea->legalizarPorCantidad('0.5'));
        $this->assertRegla(CodigoError::LINEA_SERIALIZADA,
            fn () => $this->linea('10', serializado: true)->legalizarPorCantidad('1'));
    }

    public function test_legalizar_por_cantidad_admite_decimales_en_kg(): void
    {
        $linea = $this->linea('2.5', unidad: 'KG');

        $linea->legalizarPorCantidad('2.125');

        $this->assertSame('0.375', $linea->en_cuarentena);
    }

    // ---------------- legalizar por seriales (Modo A serializado y Modo C) ----------------

    public function test_legalizar_seriales_legaliza_la_linea_y_cada_serial(): void
    {
        $linea = $this->linea('3', serializado: true);
        $a = $this->serial('ABCDE-00001-0001');
        $b = $this->serial('ABCDE-00001-0002');

        $this->assertSame('2.000', $linea->legalizarSeriales([$a, $b]));
        $this->assertSame('2.000', $linea->cantidad_legalizada);
        $this->assertSame(EstadoLegalSerial::LEGALIZADO, $a->estado_legal);
        $this->assertSame(EstadoLegalSerial::LEGALIZADO, $b->estado_legal);
    }

    public function test_legalizar_seriales_rechaza_sin_cambiar_nada(): void
    {
        $linea = $this->linea('3', serializado: true);
        $bueno = $this->serial('ABCDE-00001-0001');

        $this->assertRegla(CodigoError::SERIAL_YA_LEGALIZADO, fn () => $linea->legalizarSeriales([
            $bueno, $this->serial('ABCDE-00001-0002', estado: EstadoLegalSerial::LEGALIZADO),
        ]));
        $this->assertRegla(CodigoError::SERIAL_OTRA_LINEA, fn () => $linea->legalizarSeriales([$bueno, $this->serial('ABCDE-00001-0003', 2)]));
        $this->assertRegla(CodigoError::SERIAL_REPETIDO, fn () => $linea->legalizarSeriales([$bueno, $this->serial('ABCDE-00001-0001')]));
        $this->assertRegla(CodigoError::CANTIDAD_INVALIDA, fn () => $linea->legalizarSeriales([]));

        $this->assertSame('0.000', $linea->cantidad_legalizada);
        $this->assertSame(EstadoLegalSerial::EN_CUARENTENA, $bueno->estado_legal);
    }

    public function test_legalizar_seriales_exige_linea_serializada_y_cuarentena(): void
    {
        $this->assertRegla(CodigoError::LINEA_NO_SERIALIZADA,
            fn () => $this->linea('3')->legalizarSeriales([$this->serial('ABCDE-00001-0001')]));

        $this->assertRegla(CodigoError::CANTIDAD_SUPERA_CUARENTENA, fn () => $this->linea('2', '1', serializado: true)->legalizarSeriales([
            $this->serial('ABCDE-00001-0001'), $this->serial('ABCDE-00001-0002'),
        ]));
    }
}

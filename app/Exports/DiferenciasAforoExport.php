<?php

namespace App\Exports;

use App\Models\Aforo;
use App\Models\AforoDetalle;
use App\Support\Milesimas;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Reporte de diferencias del aforo (A8). Arma los datos una sola vez para el PDF (vista reportes.aforo-diferencias)
 * y para el Excel. Usa PhpSpreadsheet directo (composer require phpoffice/phpspreadsheet).
 */
final class DiferenciasAforoExport
{
    private const ENCABEZADOS = ['Pallet', 'Bodega', 'Bulto', 'SKU', 'Descripción', 'Unidad', 'Manifiesto', 'Contada', 'Diferencia', 'Seriales'];

    private ?array $datos = null;

    public function __construct(private readonly Aforo $aforo) {}

    /** @return array{aforo: Aforo, filas: list<array<string, mixed>>, totales: array<string, mixed>, generado: string} */
    public function datos(): array
    {
        if ($this->datos !== null) {
            return $this->datos;
        }

        $this->aforo->loadMissing([
            'bl.cliente:id,codigo_corto,razon_social',
            'detalles.bultoDetalle' => fn ($q) => $q->withCount('seriales'),
            'detalles.bultoDetalle.producto:id,sku,descripcion,unidad_medida',
            'detalles.bultoDetalle.bulto.pallet.bodega:id,codigo',
        ]);

        $filas = $this->aforo->detalles
            ->sortBy(fn (AforoDetalle $d) => $d->bultoDetalle->bulto->codigo.$d->bultoDetalle->producto->sku)
            ->map(function (AforoDetalle $d) {
                $linea = $d->bultoDetalle;
                $dif = $d->cantidad_contada === null ? null : Milesimas::de($d->cantidad_contada) - Milesimas::de($d->cantidad_manifiesto);

                return [
                    'pallet'      => $linea->bulto->pallet?->codigo,
                    'bodega'      => $linea->bulto->pallet?->bodega?->codigo,
                    'bulto'       => $linea->bulto->codigo,
                    'sku'         => $linea->producto->sku,
                    'descripcion' => $linea->producto->descripcion,
                    'unidad'      => $linea->producto->unidad_medida instanceof \BackedEnum ? $linea->producto->unidad_medida->value : (string) $linea->producto->unidad_medida,
                    'manifiesto'  => $d->cantidad_manifiesto,
                    'contada'     => $d->cantidad_contada,
                    'diferencia'  => $dif === null ? null : Milesimas::texto($dif),
                    'signo'       => $dif === null ? 0 : $dif <=> 0,
                    'seriales'    => $linea->seriales_count,
                ];
            })
            ->values()
            ->all();

        $con = array_filter($filas, fn ($f) => $f['signo'] !== 0);

        return $this->datos = [
            'aforo'    => $this->aforo,
            'filas'    => $filas,
            'totales'  => [
                'lineas'          => count($filas),
                'con_diferencia'  => count($con),
                'faltantes'       => count(array_filter($con, fn ($f) => $f['signo'] < 0)),
                'sobrantes'       => count(array_filter($con, fn ($f) => $f['signo'] > 0)),
                'seriales'        => array_sum(array_column($filas, 'seriales')),
            ],
            'generado' => now()->timezone(config('kosmos.zona_horaria', 'America/Bogota'))->format('d/m/Y H:i'),
        ];
    }

    /** Escribe el .xlsx en el disco local (ruta relativa al disco). */
    public function guardar(string $ruta): void
    {
        $d = $this->datos();
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet()->setTitle('Diferencias');

        $hoja->fromArray([
            ["Reporte de diferencias · Aforo AFO-{$d['aforo']->id}"],
            ['BL', $d['aforo']->bl->numero_bl, 'Cliente', $d['aforo']->bl->cliente->razon_social],
            ['Generado', $d['generado'], 'Líneas', $d['totales']['lineas'], 'Con diferencia', $d['totales']['con_diferencia']],
            [],
            self::ENCABEZADOS,
        ]);
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $hoja->getStyle('A5:J5')->getFont()->setBold(true);
        $hoja->getStyle('A5:J5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');

        $fila = 6;
        foreach ($d['filas'] as $f) {
            $hoja->fromArray([
                $f['pallet'], $f['bodega'], $f['bulto'], $f['sku'], $f['descripcion'], $f['unidad'],
                (float) $f['manifiesto'], $f['contada'] === null ? null : (float) $f['contada'],
                $f['diferencia'] === null ? null : (float) $f['diferencia'], $f['seriales'],
            ], null, "A{$fila}");

            if ($f['signo'] !== 0) {
                $hoja->getStyle("I{$fila}")->getFont()->setBold(true)->getColor()->setRGB($f['signo'] < 0 ? 'B91C1C' : '15803D');
            }
            $fila++;
        }

        $hoja->getStyle('G6:I'.max(6, $fila - 1))->getNumberFormat()->setFormatCode('#,##0.000');
        foreach (range('A', 'J') as $col) {
            $hoja->getColumnDimension($col)->setAutoSize(true);
        }

        $temporal = tempnam(sys_get_temp_dir(), 'afo');
        (new Xlsx($libro))->save($temporal);
        Storage::disk('local')->put($ruta, file_get_contents($temporal));
        @unlink($temporal);
        $libro->disconnectWorksheets();
    }
}

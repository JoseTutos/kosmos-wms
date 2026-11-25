<?php

namespace App\Exports;

use App\Enums\TipoDocumentoAutorizacion;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CUS-012 (H2): exporta el histórico filtrado, una fila por línea legalizada (bulto · producto) con sus seriales.
 * Excel con PhpSpreadsheet; CSV en streaming (separador ";" y BOM UTF-8 para que Excel en español lo abra bien).
 * Las cantidades se escriben como texto numérico, sin pasar por float.
 */
final class LegalizacionesExport
{
    private const ENCABEZADOS = [
        'Fecha y hora', 'Legalización', 'BL', 'Cliente', 'Razón social', 'Tipo de documento', 'Número de autorización',
        'Fecha de autorización', 'Usuario', 'Bulto', 'SKU', 'Descripción', 'Unidad', 'Cantidad', 'Seriales (n)', 'Seriales',
    ];

    private const LOTE = 1000;

    public function __construct(private readonly Builder $filas) {}

    public function xlsx(string $nombre): StreamedResponse
    {
        return new StreamedResponse(function () {
            $libro = new Spreadsheet;
            $hoja = $libro->getActiveSheet();
            $hoja->setTitle('Legalizaciones');
            $hoja->fromArray(self::ENCABEZADOS);
            $hoja->getStyle('A1:'.Coordinate::stringFromColumnIndex(count(self::ENCABEZADOS)).'1')->getFont()->setBold(true);
            $hoja->freezePane('A2');

            $fila = 2;
            foreach ($this->filas->lazy(self::LOTE) as $registro) {
                foreach ($this->valores($registro) as $i => $valor) {
                    $columna = Coordinate::stringFromColumnIndex($i + 1);
                    $tipo = in_array($i, [13, 14], true) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING;
                    $hoja->setCellValueExplicit("{$columna}{$fila}", $valor, $valor === '' ? DataType::TYPE_STRING : $tipo);
                }
                $fila++;
            }

            foreach (range(1, count(self::ENCABEZADOS) - 1) as $i) {
                $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            }

            (new Xlsx($libro))->save('php://output');
            $libro->disconnectWorksheets();
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$nombre}.xlsx\"",
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    public function csv(string $nombre): StreamedResponse
    {
        return new StreamedResponse(function () {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, self::ENCABEZADOS, ';', '"', '');

            foreach ($this->filas->lazy(self::LOTE) as $registro) {
                fputcsv($salida, $this->valores($registro), ';', '"', '');
            }

            fclose($salida);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombre}.csv\"",
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    /** @return list<string> */
    private function valores(object $r): array
    {
        $zona = config('kosmos.zona_horaria', 'America/Bogota');

        return [
            CarbonImmutable::parse($r->created_at)->setTimezone($zona)->format('Y-m-d H:i'),
            $r->consecutivo,
            $r->numero_bl,
            $r->codigo_corto,
            $r->razon_social,
            TipoDocumentoAutorizacion::tryFrom($r->tipo_documento)?->etiqueta() ?? $r->tipo_documento,
            $r->numero_autorizacion,
            CarbonImmutable::parse($r->fecha_autorizacion)->format('Y-m-d'),
            $r->usuario,
            $r->bulto,
            $r->sku,
            $r->descripcion,
            $r->unidad_medida,
            (string) $r->cantidad,
            (string) ($r->total_seriales ?? 0),
            (string) ($r->seriales ?? ''),
        ];
    }
}

<?php

namespace App\Services\Maestros;

use App\Enums\TipoAlmacenamiento;
use App\Enums\UnidadMedida;
use App\Exceptions\ReglaNegocioException;
use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

/**
 * Kosmos WMS · Capa de Aplicación (Services) · CUS-002 Importación masiva de productos (CSV)
 *
 * Por qué es un servicio: lee un archivo, valida N filas contra la BD y crea N productos
 * (cada uno con su SKU consecutivo) en UNA transacción. Eso no cabe en un controlador delgado.
 *
 * Cumple:
 *   - CUS-002: importación masiva por CSV para un cliente ACTIVO.
 *   - Decisión de diseño: TODO O NADA. Primero se validan todas las filas; si alguna falla,
 *     no se guarda ninguna y se devuelve el reporte fila por fila. No quedan importaciones
 *     a medias ni duplicados al volver a subir el archivo corregido.
 *   - RS-07: el SKU lo genera el modelo (Producto::creating → Consecutivo::siguiente) dentro
 *     de la misma transacción: si algo falla, también se revierten los consecutivos.
 *
 * Formato del archivo (la plantilla se descarga desde la pantalla):
 *   descripcion;tipo_almacenamiento;unidad_medida;peso_unitario_kg;stock_minimo
 *   - Separador ';' o ',' (se detecta solo). Excel en español guarda con ';'.
 *   - Decimales con coma o punto: 0,015 o 0.015.
 *   - stock_minimo es opcional (vacío = 0).
 *
 * Rendimiento y seguridad:
 *   - Máximo MAX_FILAS filas y 2 MB (ImportarProductosRequest), para no bloquear el servidor.
 *   - Se lee línea por línea (fgetcsv), sin cargar el archivo completo en memoria.
 *   - El cliente se asigna en memoria a cada producto (setRelation): evita una consulta por fila.
 *   - El reporte se corta en MAX_ERRORES para no generar páginas gigantes.
 */
final class ProductoImportService
{
    public const MAX_FILAS = 2000;

    private const MAX_ERRORES = 100;

    private const OBLIGATORIAS = ['descripcion', 'tipo_almacenamiento', 'unidad_medida', 'peso_unitario_kg'];

    private const OPCIONALES = ['stock_minimo'];

    /**
     * @return array{importados: int, errores: array<int, array{fila: int, mensaje: string}>}
     */
    public function importar(Cliente $cliente, UploadedFile $archivo): array
    {
        if (! $cliente->estaActivo()) {
            throw new ReglaNegocioException('Solo se importan productos para clientes ACTIVOS.');
        }

        [$filas, $errores] = $this->leer($archivo);

        if (! $errores) {
            $errores = $this->validar($cliente, $filas);
        }

        if ($errores) {
            return ['importados' => 0, 'errores' => array_slice($errores, 0, self::MAX_ERRORES)];
        }

        DB::transaction(function () use ($cliente, $filas) {
            foreach ($filas as $fila) {
                $producto = new Producto(['cliente_id' => $cliente->id] + $fila['datos']);
                $producto->setRelation('cliente', $cliente); // el hook 'creating' no vuelve a consultarlo
                $producto->save();                           // SKU automático en el modelo
            }
        });

        return ['importados' => count($filas), 'errores' => []];
    }

    /**
     * Lee el CSV y normaliza cada fila.
     *
     * @return array{0: array<int, array{fila: int, datos: array<string, string>}>, 1: array<int, array{fila: int, mensaje: string}>}
     */
    private function leer(UploadedFile $archivo): array
    {
        $handle = fopen($archivo->getRealPath(), 'rb');
        if ($handle === false) {
            return [[], [['fila' => 0, 'mensaje' => 'No se pudo leer el archivo.']]];
        }

        try {
            $primera = fgets($handle);
            if ($primera === false) {
                return [[], [['fila' => 0, 'mensaje' => 'El archivo está vacío.']]];
            }

            $primera = preg_replace('/^\xEF\xBB\xBF/', '', $primera); // BOM de Excel
            $separador = substr_count($primera, ';') >= substr_count($primera, ',') ? ';' : ',';

            $encabezado = array_map(
                fn ($c) => mb_strtolower(trim((string) $c)),
                str_getcsv(trim($primera), $separador, '"', '')
            );

            $faltan = array_diff(self::OBLIGATORIAS, $encabezado);
            if ($faltan) {
                return [[], [['fila' => 1, 'mensaje' => 'Faltan columnas en el encabezado: '.implode(', ', $faltan).'. Use la plantilla.']]];
            }

            $indices = array_flip($encabezado);
            $filas = [];
            $numero = 1;

            while (($celdas = fgetcsv($handle, 0, $separador, '"', '')) !== false) {
                $numero++;

                if ($celdas === [null] || implode('', array_map('trim', $celdas)) === '') {
                    continue; // fila vacía
                }

                if (count($filas) >= self::MAX_FILAS) {
                    return [[], [['fila' => $numero, 'mensaje' => 'El archivo supera el máximo de '.self::MAX_FILAS.' productos. Divídalo en varios archivos.']]];
                }

                $valor = fn (string $col) => isset($indices[$col]) ? trim((string) ($celdas[$indices[$col]] ?? '')) : '';

                $filas[] = [
                    'fila'  => $numero,
                    'datos' => [
                        'descripcion'         => $valor('descripcion'),
                        'tipo_almacenamiento' => mb_strtoupper($valor('tipo_almacenamiento')),
                        'unidad_medida'       => mb_strtoupper($valor('unidad_medida')),
                        'peso_unitario_kg'    => $this->decimal($valor('peso_unitario_kg')),
                        'stock_minimo'        => $this->decimal($valor('stock_minimo')) ?: '0',
                    ],
                ];
            }
        } finally {
            fclose($handle);
        }

        if (! $filas) {
            return [[], [['fila' => 0, 'mensaje' => 'El archivo no tiene productos debajo del encabezado.']]];
        }

        return [$filas, []];
    }

    /**
     * Valida todas las filas y los duplicados (dentro del archivo y contra lo ya registrado).
     *
     * @param  array<int, array{fila: int, datos: array<string, string>}>  $filas
     * @return array<int, array{fila: int, mensaje: string}>
     */
    private function validar(Cliente $cliente, array $filas): array
    {
        $errores = [];

        $reglas = [
            'descripcion'         => ['required', 'string', 'max:255'],
            'tipo_almacenamiento' => ['required', new Enum(TipoAlmacenamiento::class)],
            'unidad_medida'       => ['required', new Enum(UnidadMedida::class)],
            'peso_unitario_kg'    => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999'],
            'stock_minimo'        => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:99999999999'],
        ];

        $atributos = [
            'descripcion' => 'descripción', 'tipo_almacenamiento' => 'tipo de almacenamiento',
            'unidad_medida' => 'unidad de medida', 'peso_unitario_kg' => 'peso unitario (kg)', 'stock_minimo' => 'stock mínimo',
        ];

        // Descripciones que ya tiene el cliente (una sola consulta)
        $existentes = $cliente->productos()->pluck('descripcion')
            ->mapWithKeys(fn ($d) => [mb_strtolower(trim($d)) => true])->all();

        $enArchivo = [];

        foreach ($filas as $fila) {
            $v = Validator::make($fila['datos'], $reglas, [], $atributos);

            foreach ($v->errors()->all() as $mensaje) {
                $errores[] = ['fila' => $fila['fila'], 'mensaje' => $mensaje];
            }

            $clave = mb_strtolower($fila['datos']['descripcion']);

            if ($clave !== '' && isset($existentes[$clave])) {
                $errores[] = ['fila' => $fila['fila'], 'mensaje' => "El cliente ya tiene un producto con la descripción «{$fila['datos']['descripcion']}»."];
            } elseif ($clave !== '' && isset($enArchivo[$clave])) {
                $errores[] = ['fila' => $fila['fila'], 'mensaje' => "Descripción repetida en el archivo (también en la fila {$enArchivo[$clave]})."];
            }

            $enArchivo[$clave] ??= $fila['fila'];

            if (count($errores) >= self::MAX_ERRORES) {
                break;
            }
        }

        return $errores;
    }

    /**
     * Normaliza el separador decimal: "1.234,5" o "1234,5" → "1234.5"; "1234.5" queda igual.
     * No redondea ni corta: si trae más de 3 decimales o no es un número, la regla
     * 'decimal:0,3' / 'numeric' lo reporta con su número de fila.
     */
    private function decimal(string $valor): string
    {
        return str_contains($valor, ',') ? str_replace(['.', ','], ['', '.'], $valor) : $valor;
    }
}

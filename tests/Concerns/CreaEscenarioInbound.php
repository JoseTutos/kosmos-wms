<?php

namespace Tests\Concerns;

use App\Enums\CodigoError;
use App\Exceptions\ReglaNegocioException;
use App\Models\Bl;
use App\Models\BlDetalle;
use App\Models\Bodega;
use App\Models\Bulto;
use App\Models\Cliente;
use App\Models\Pallet;
use App\Models\Producto;
use App\Models\Ubicacion;
use App\Services\Nucleo\ConsecutivoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Datos mínimos del Módulo 2, creados con el mismo dominio y Núcleo que usa la app. */
trait CreaEscenarioInbound
{
    use CreaUsuarios;

    protected function crearCliente(): Cliente
    {
        $cliente = new Cliente([
            'nit'             => (string) random_int(100000000, 999999999),
            'codigo_corto'    => substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5),
            'razon_social'    => 'Cliente de prueba '.Str::random(4),
            'direccion'       => 'Zona Franca, Cartagena',
            'telefono'        => '6056600000',
            'contacto_nombre' => 'Contacto de prueba',
        ]);
        $cliente->save();

        return $cliente;
    }

    /** Pasa por Producto::creating → ConsecutivoService (SKU automático). */
    protected function crearProducto(Cliente $cliente): Producto
    {
        return DB::transaction(fn () => Producto::create([
            'cliente_id'          => $cliente->id,
            'descripcion'         => 'Producto '.Str::random(8),
            'tipo_almacenamiento' => 'PALETIZADO',
            'unidad_medida'       => 'UND',
            'peso_unitario_kg'    => '1.000',
        ]));
    }

    protected function crearBodega(bool $conUbicaciones = false): Bodega
    {
        return Bodega::create([
            'codigo'             => 'BOD-'.random_int(1000, 9999),
            'nombre'             => 'Bodega de prueba',
            'tamano_m2'          => '100.00',
            'maneja_ubicaciones' => $conUbicaciones,
        ]);
    }

    protected function crearUbicacion(Bodega $bodega): Ubicacion
    {
        return Ubicacion::create(['bodega_id' => $bodega->id, 'codigo' => 'A-'.random_int(100, 999)]);
    }

    /** BL con una línea de manifiesto por producto. */
    protected function crearBl(Cliente $cliente, Producto ...$productos): Bl
    {
        $bl = Bl::create([
            'numero_bl'         => 'BL-'.Str::upper(Str::random(8)),
            'cliente_id'        => $cliente->id,
            'fecha_ingreso'     => now()->toDateString(),
            'factura_comercial' => 'FC-0001',
        ]);

        foreach ($productos as $producto) {
            BlDetalle::create([
                'bl_id'              => $bl->id,
                'producto_id'        => $producto->id,
                'bultos_esperados'   => 10,
                'unidades_esperadas' => '100.000',
                'peso_bruto_kg'      => '500.000',
            ]);
        }

        return $bl->refresh();
    }

    protected function crearPallet(Bl $bl): Pallet
    {
        return Pallet::create(['codigo' => app(ConsecutivoService::class)->codigoPallet(), 'bl_id' => $bl->id])->refresh();
    }

    /** Bulto guardado; con producto, también su línea. */
    protected function crearBulto(Bl $bl, ?Producto $producto = null, string $cantidad = '10'): Bulto
    {
        $bulto = Bulto::create([
            'codigo'        => app(ConsecutivoService::class)->codigoBulto(),
            'bl_id'         => $bl->id,
            'peso_bruto_kg' => '25.000',
        ])->refresh();

        if ($producto) {
            $bulto->agregarLinea($producto, $cantidad)->save();
        }

        return $bulto;
    }

    /** Pallet con un bulto, cerrado y ubicado en la bodega. */
    protected function palletUbicado(Bl $bl, Producto $producto, Bodega $bodega): Pallet
    {
        $pallet = $this->crearPallet($bl);
        $bulto = $this->crearBulto($bl, $producto);

        $pallet->agregarBulto($bulto);
        $bulto->save();
        $pallet->cerrar();
        $pallet->ubicar($bodega);
        $pallet->save();

        return $pallet;
    }

    protected function assertRegla(CodigoError $esperado, callable $accion): void
    {
        try {
            $accion();
        } catch (ReglaNegocioException $e) {
            $this->assertSame($esperado, $e->codigo, "Se esperaba {$esperado->value} y llegó {$e->codigo->value}: {$e->getMessage()}");

            return;
        }

        $this->fail("Se esperaba ReglaNegocioException {$esperado->value}.");
    }
}

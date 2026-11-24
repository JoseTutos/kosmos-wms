<?php

namespace App\Support;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Kosmos WMS · Soporte de presentación · Menú principal por rol
 *
 * Cumple:
 *   - RS-19 / RE-7: cada usuario ve solo los módulos de su rol (segregación de funciones).
 *   - Decisión de diseño (croquis 3): la MISMA lista alimenta el menú lateral y las
 *     tarjetas del Inicio, así que nunca muestran cosas distintas.
 *
 * Restricciones:
 *   - Mostrar un enlace NO da permiso: cada ruta se protege con el middleware 'role'.
 *     Los roles de cada ítem deben coincidir con los de su ruta.
 *   - Un ítem solo aparece si su ruta ya existe (Route::has). Así el menú crece solo
 *     a medida que se construyen los módulos, sin enlaces rotos.
 *   - Los nombres de ruta de módulos futuros son la convención propuesta; ajústelos
 *     al registrar cada módulo.
 */
final class MenuPrincipal
{
    private const SECCIONES = [
        ['titulo' => 'Administración', 'items' => [
            ['label' => 'Usuarios', 'ruta' => 'usuarios.index', 'activo' => 'usuarios.*', 'icono' => 'usuarios',
             'desc' => 'Cuentas, roles y accesos.', 'roles' => [Rol::ADMIN_SISTEMA]],
        ]],
        ['titulo' => 'Maestros', 'items' => [
            ['label' => 'Clientes', 'ruta' => 'clientes.index', 'activo' => 'clientes.*', 'icono' => 'clientes',
             'desc' => 'Importadores, NIT y código corto.', 'roles' => [Rol::ADMIN_MAESTROS]],
            ['label' => 'Productos', 'ruta' => 'productos.index', 'activo' => 'productos.*', 'icono' => 'bl',
             'desc' => 'SKU automático e importación CSV.', 'roles' => [Rol::ADMIN_MAESTROS]],
            ['label' => 'Bodegas', 'ruta' => 'bodegas.index', 'activo' => 'bodegas.*', 'icono' => 'inventario',
             'desc' => 'Bodegas, ubicaciones y ocupación.', 'roles' => [Rol::ADMIN_MAESTROS]],
        ]],
        ['titulo' => 'Entrada', 'items' => [
            ['label' => 'BL', 'ruta' => 'bls.index', 'activo' => 'bls.*', 'icono' => 'bl',
             'desc' => 'Registrar y consultar manifiestos.', 'roles' => [Rol::SUPERVISOR]],
            ['label' => 'Aforos', 'ruta' => 'aforos.index', 'activo' => 'aforos.*', 'icono' => 'aforo',
             'desc' => 'Activar y cerrar conteos detallados.', 'roles' => [Rol::SUPERVISOR]],
            ['label' => 'Etiquetas', 'ruta' => 'etiquetas.index', 'activo' => 'etiquetas.*', 'icono' => 'etiqueta',
             'desc' => 'Reimprimir con motivo.', 'roles' => [Rol::SUPERVISOR]],
        ]],
        ['titulo' => 'Control y legalización', 'items' => [
            ['label' => 'Legalizaciones', 'ruta' => 'legalizaciones.index', 'activo' => 'legalizaciones.*', 'icono' => 'legalizacion',
             'desc' => 'Por bulto, cantidad o serial.', 'roles' => [Rol::ANALISTA, Rol::SUPERVISOR, Rol::GERENTE]],
            ['label' => 'Cuarentena', 'ruta' => 'cuarentena.index', 'activo' => 'cuarentena.*', 'icono' => 'cuarentena',
             'desc' => 'Pendiente de legalizar.', 'roles' => [Rol::SUPERVISOR, Rol::ANALISTA, Rol::GERENTE]],
            ['label' => 'Ajustes de inventario', 'ruta' => 'ajustes.index', 'activo' => 'ajustes.*', 'icono' => 'ajuste',
             'desc' => 'Corrección de inventario con motivo.', 'roles' => [Rol::SUPERVISOR]],
        ]],
        ['titulo' => 'Salida', 'items' => [
            ['label' => 'Órdenes de salida', 'ruta' => 'ordenes-salida.index', 'activo' => 'ordenes-salida.*', 'icono' => 'salida',
             'desc' => 'Crear, anular y cerrar parcial.', 'roles' => [Rol::ANALISTA, Rol::SUPERVISOR]],
        ]],
        ['titulo' => 'Consultas', 'items' => [
            ['label' => 'Inventario', 'ruta' => 'inventario.index', 'activo' => 'inventario.*', 'icono' => 'inventario',
             'desc' => 'Cliente, bodega, producto y BL.', 'roles' => [Rol::ANALISTA, Rol::SUPERVISOR, Rol::GERENTE]],
            ['label' => 'Kardex', 'ruta' => 'kardex.index', 'activo' => 'kardex.*', 'icono' => 'kardex',
             'desc' => 'Movimientos cronológicos.', 'roles' => [Rol::ANALISTA, Rol::SUPERVISOR, Rol::GERENTE]],
            ['label' => 'Historial de despachos', 'ruta' => 'despachos.index', 'activo' => 'despachos.*', 'icono' => 'despachos',
             'desc' => 'Notas de entrega por BL.', 'roles' => [Rol::ANALISTA, Rol::SUPERVISOR, Rol::GERENTE]],
        ]],
        ['titulo' => 'Auditoría', 'items' => [
            ['label' => 'Bitácora del sistema', 'ruta' => 'auditoria.index', 'activo' => 'auditoria.*', 'icono' => 'bitacora',
             'desc' => 'Registro de solo inserción, 5 años.', 'roles' => [Rol::ADMIN_SISTEMA, Rol::GERENTE]],
        ]],
    ];

    /** Botón destacado del Inicio: la tarea más frecuente de cada rol. */
    private const ACCION_PRINCIPAL = [
        'ADMIN_SISTEMA'  => ['label' => 'Nuevo usuario', 'ruta' => 'usuarios.create'],
        'ADMIN_MAESTROS' => ['label' => 'Nuevo cliente', 'ruta' => 'clientes.create'],
        'SUPERVISOR'     => ['label' => 'Registrar BL', 'ruta' => 'bls.create'],
        'ANALISTA'       => ['label' => 'Nueva legalización', 'ruta' => 'legalizaciones.create'],
    ];

    /** @return array<int, array{titulo: string, items: array<int, array>}> */
    public static function secciones(User $user): array
    {
        $resultado = [];

        foreach (self::SECCIONES as $seccion) {
            $items = array_values(array_filter(
                $seccion['items'],
                fn (array $item) => $user->tieneRol(...$item['roles']) && Route::has($item['ruta']),
            ));

            if ($items) {
                $resultado[] = ['titulo' => $seccion['titulo'], 'items' => $items];
            }
        }

        return $resultado;
    }

    /** @return array{label: string, ruta: string}|null */
    public static function accionPrincipal(User $user): ?array
    {
        $accion = self::ACCION_PRINCIPAL[$user->rol->value] ?? null;

        return $accion && Route::has($accion['ruta']) ? $accion : null;
    }
}

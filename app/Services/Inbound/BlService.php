<?php

namespace App\Services\Inbound;

use App\Enums\CodigoError;
use App\Enums\EstadoBl;
use App\Exceptions\ReglaNegocioException;
use App\Models\Bl;
use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * CUS-005 Registrar BL (Supervisor).
 * Reglas: solo en PROGRAMADO (FA-03), cliente ACTIVO, productos ACTIVOS del mismo cliente,
 * un renglón por producto (B7, B8). El acta de preinspección es opcional y editable hasta RECIBIDO (B6, RS-28).
 * Las FormRequest validan el formato; aquí se repiten las reglas de negocio (otros llamadores, carreras).
 */
final class BlService
{
    private const DISCO = 'local'; // storage/app/private

    /**
     * @param array{numero_bl: string, cliente_id: int, fecha_ingreso: string, factura_comercial: string,
     *              fecha_preinspeccion?: ?string, detalles: list<array<string, mixed>>} $datos
     */
    public function crear(array $datos, ?UploadedFile $acta = null): Bl
    {
        return DB::transaction(function () use ($datos, $acta) {
            $cliente = Cliente::query()->findOrFail($datos['cliente_id']);
            $this->exigirClienteActivo($cliente);
            $this->validarDetalle($cliente, $datos['detalles']);

            $bl = Bl::create([
                'numero_bl'           => $datos['numero_bl'],
                'cliente_id'          => $cliente->id,
                'fecha_ingreso'       => $datos['fecha_ingreso'],
                'factura_comercial'   => $datos['factura_comercial'],
                'fecha_preinspeccion' => $datos['fecha_preinspeccion'] ?? null,
            ]);

            $this->sincronizarDetalle($bl, $datos['detalles']);

            if ($acta) {
                $bl->acta_preinspeccion_path = $this->guardarActa($bl, $acta);
                $bl->save();
            }

            $this->auditar($bl, 'BL registrado', ['renglones' => count($datos['detalles'])]);

            return $bl;
        });
    }

    /** Edita encabezado y manifiesto. El cliente no cambia (sus productos definen el detalle). */
    public function actualizar(Bl $bl, array $datos, ?UploadedFile $acta = null, bool $quitarActa = false): Bl
    {
        return DB::transaction(function () use ($bl, $datos, $acta, $quitarActa) {
            $bl = Bl::query()->whereKey($bl->getKey())->lockForUpdate()->firstOrFail();
            $this->exigirEditable($bl);

            $cliente = $bl->cliente;
            $this->exigirClienteActivo($cliente);
            $this->validarDetalle($cliente, $datos['detalles']);

            $bl->fill([
                'numero_bl'           => $datos['numero_bl'],
                'fecha_ingreso'       => $datos['fecha_ingreso'],
                'factura_comercial'   => $datos['factura_comercial'],
                'fecha_preinspeccion' => $datos['fecha_preinspeccion'] ?? null,
            ]);

            $this->reemplazarActa($bl, $acta, $quitarActa);
            $bl->save();

            $this->sincronizarDetalle($bl, $datos['detalles']);
            $this->auditar($bl, 'BL actualizado', ['renglones' => count($datos['detalles'])]);

            return $bl;
        });
    }

    /** B6 / RS-28: fecha y acta de preinspección, mientras el BL no esté RECIBIDO. */
    public function registrarPreinspeccion(Bl $bl, ?string $fecha, ?UploadedFile $acta = null, bool $quitarActa = false): Bl
    {
        return DB::transaction(function () use ($bl, $fecha, $acta, $quitarActa) {
            $bl = Bl::query()->whereKey($bl->getKey())->lockForUpdate()->firstOrFail();

            if (! self::admitePreinspeccion($bl)) {
                throw new ReglaNegocioException(CodigoError::BL_ESTADO_INVALIDO, "La preinspección del BL {$bl->numero_bl} ya no se puede modificar.");
            }

            $bl->fecha_preinspeccion = $fecha;
            $this->reemplazarActa($bl, $acta, $quitarActa);
            $bl->save();

            $this->auditar($bl, 'preinspección registrada', ['fecha' => $fecha, 'acta' => (bool) $bl->acta_preinspeccion_path]);

            return $bl;
        });
    }

    /** B2: borrado físico solo en PROGRAMADO y sin bultos ni pallets. Queda en la bitácora. */
    public function eliminar(Bl $bl): void
    {
        $acta = DB::transaction(function () use ($bl) {
            $bl = Bl::query()->whereKey($bl->getKey())->lockForUpdate()->firstOrFail();
            $this->exigirEditable($bl);

            if ($bl->bultos()->exists() || $bl->pallets()->exists()) {
                throw new ReglaNegocioException(CodigoError::BL_CON_RECEPCION, datos: ['bl' => $bl->numero_bl]);
            }

            $this->auditar($bl, 'BL eliminado', ['numero_bl' => $bl->numero_bl, 'cliente_id' => $bl->cliente_id]);

            $acta = $bl->acta_preinspeccion_path;
            $bl->detalles()->delete();
            $bl->delete();

            return $acta;
        });

        if ($acta) {
            Storage::disk(self::DISCO)->delete($acta); // después del commit: si la BD falla, el archivo sigue
        }
    }

    public static function admitePreinspeccion(Bl $bl): bool
    {
        return in_array($bl->estado, [EstadoBl::PROGRAMADO, EstadoBl::EN_RECEPCION], true);
    }

    public function rutaActa(Bl $bl): ?string
    {
        return $bl->acta_preinspeccion_path && Storage::disk(self::DISCO)->exists($bl->acta_preinspeccion_path)
            ? Storage::disk(self::DISCO)->path($bl->acta_preinspeccion_path)
            : null;
    }

    // ---------------- Apoyo ----------------

    private function exigirEditable(Bl $bl): void
    {
        if (! $bl->esEditable()) {
            throw new ReglaNegocioException(
                CodigoError::BL_ESTADO_INVALIDO,
                "El BL {$bl->numero_bl} está {$bl->estado->value}: solo se modifica mientras está PROGRAMADO.",
                ['bl' => $bl->numero_bl, 'estado' => $bl->estado->value],
            );
        }
    }

    private function exigirClienteActivo(Cliente $cliente): void
    {
        if (! $cliente->estaActivo()) {
            throw new ReglaNegocioException(CodigoError::CLIENTE_INACTIVO, datos: ['cliente' => $cliente->codigo_corto]);
        }
    }

    /** B7, B8: productos existentes, del cliente, ACTIVOS y sin repetir. */
    private function validarDetalle(Cliente $cliente, array $detalles): void
    {
        $ids = array_map(fn ($d) => (int) $d['producto_id'], $detalles);

        if ($ids === [] || count($ids) !== count(array_unique($ids))) {
            throw new ReglaNegocioException('El manifiesto debe tener al menos un producto y cada producto una sola vez.');
        }

        $productos = Producto::query()->whereIn('id', $ids)->get()->keyBy('id');

        foreach ($ids as $id) {
            $producto = $productos->get($id);

            if (! $producto || (int) $producto->cliente_id !== (int) $cliente->id) {
                throw new ReglaNegocioException(CodigoError::PRODUCTO_OTRO_CLIENTE, datos: ['producto_id' => $id, 'cliente' => $cliente->codigo_corto]);
            }
            if (! $producto->estaActivo()) {
                throw new ReglaNegocioException(CodigoError::PRODUCTO_INACTIVO, "El producto {$producto->sku} está inactivo.", ['sku' => $producto->sku]);
            }
        }
    }

    /** Un renglón por producto: actualiza los que siguen, crea los nuevos y borra los que se quitaron. */
    private function sincronizarDetalle(Bl $bl, array $detalles): void
    {
        $ids = array_map(fn ($d) => (int) $d['producto_id'], $detalles);

        $bl->detalles()->whereNotIn('producto_id', $ids)->delete();

        foreach ($detalles as $d) {
            $bl->detalles()->updateOrCreate(
                ['producto_id' => (int) $d['producto_id']],
                [
                    'bultos_esperados'   => (int) $d['bultos_esperados'],
                    'unidades_esperadas' => (string) $d['unidades_esperadas'],
                    'peso_bruto_kg'      => (string) $d['peso_bruto_kg'],
                ],
            );
        }
    }

    private function reemplazarActa(Bl $bl, ?UploadedFile $acta, bool $quitar): void
    {
        $anterior = $bl->acta_preinspeccion_path;

        if ($acta) {
            $bl->acta_preinspeccion_path = $this->guardarActa($bl, $acta);
        } elseif ($quitar) {
            $bl->acta_preinspeccion_path = null;
        }

        if ($anterior && $anterior !== $bl->acta_preinspeccion_path) {
            DB::afterCommit(fn () => Storage::disk(self::DISCO)->delete($anterior));
        }
    }

    private function guardarActa(Bl $bl, UploadedFile $acta): string
    {
        return $acta->store("bls/{$bl->getKey()}/actas", self::DISCO);
    }

    private function auditar(Bl $bl, string $evento, array $props = []): void
    {
        activity('inbound')->performedOn($bl)->causedBy(auth()->user())
            ->withProperties($props + ['ip' => request()->ip()])
            ->log($evento);
    }
}

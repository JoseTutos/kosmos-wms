<?php

namespace App\Services\Inbound;

use App\Enums\CodigoError;
use App\Enums\EstadoBl;
use App\Enums\EstadoBulto;
use App\Enums\EstadoLegal;
use App\Enums\EstadoPallet;
use App\Enums\TipoMovimiento;
use App\Exceptions\ReglaNegocioException;
use App\Models\Bl;
use App\Models\Bulto;
use App\Models\BultoDetalle;
use App\Models\Pallet;
use App\Models\User;
use App\Services\Nucleo\ConfiguracionService;
use App\Services\Nucleo\ConsecutivoService;
use App\Services\Nucleo\MovimientoService;
use App\Support\Milesimas;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CUS-006 Recibir mercancía (app del Operario).
 *
 * Concurrencia (D8): registrar, anular y armar pallets toman el BL con FOR SHARE (varios operarios a la vez,
 * sin bloquearse entre ellos). Finalizar lo toma con FOR UPDATE: espera a que terminen las operaciones en curso
 * y, desde ese momento, cualquier bulto nuevo ve el BL RECIBIDO y se rechaza con BL_NO_EN_RECEPCION.
 */
final class RecepcionService
{
    public function __construct(
        private readonly ConsecutivoService $consecutivos,
        private readonly MovimientoService $movimientos,
        private readonly ConfiguracionService $configuracion,
    ) {}

    /**
     * CUS-006 paso 1. Si otro operario ya la inició, devuelve el BL tal cual (D8).
     * B3: la fecha de ingreso pasa a ser la real. R13: cliente y productos deben seguir ACTIVOS.
     */
    public function iniciar(Bl $bl, User $usuario): Bl
    {
        return DB::transaction(function () use ($bl, $usuario) {
            $bl = Bl::query()->whereKey($bl->getKey())->lockForUpdate()->firstOrFail();

            if ($bl->estaEnRecepcion()) {
                return $bl;
            }

            $bl->loadMissing(['cliente', 'detalles.producto']);

            if (! $bl->cliente->estaActivo()) {
                throw new ReglaNegocioException(CodigoError::CLIENTE_INACTIVO, datos: ['cliente' => $bl->cliente->codigo_corto]);
            }
            if ($bl->detalles->isEmpty()) {
                throw new ReglaNegocioException('El BL no tiene productos en el manifiesto.');
            }

            $inactivos = $bl->detalles->filter(fn ($d) => ! $d->producto->estaActivo())->map(fn ($d) => $d->producto->sku)->values();

            if ($inactivos->isNotEmpty()) {
                throw new ReglaNegocioException(CodigoError::PRODUCTO_INACTIVO, 'Hay productos inactivos en el manifiesto: '.$inactivos->implode(', ').'.', ['productos' => $inactivos->all()]);
            }

            $bl->iniciarRecepcion();                                            // dominio: solo desde PROGRAMADO
            $bl->fecha_ingreso = now(config('kosmos.zona_horaria'))->toDateString();
            $bl->save();

            $this->auditar($bl, $usuario, 'recepción iniciada');

            return $bl;
        });
    }

    /**
     * CUS-006 paso 2–3 (D7a): un bulto (mixto) o N idénticos ("Repetir bulto ×N"), todos o ninguno.
     * Si llega un pallet, cada bulto se arma de una vez en él.
     *
     * @param array{peso_bruto_kg: string, lineas: list<array{producto_id: int, cantidad: string}>, repetir?: int, pallet?: ?string} $datos
     * @return Collection<int, Bulto>
     */
    public function registrarBultos(Bl $bl, array $datos, User $usuario): Collection
    {
        $repetir = (int) ($datos['repetir'] ?? 1);
        $maximo = $this->configuracion->entero('repetir_max');

        if ($repetir < 1 || $repetir > $maximo) {
            throw new ReglaNegocioException(CodigoError::CANTIDAD_INVALIDA, "Puede repetir entre 1 y {$maximo} bultos por vez.", ['maximo' => $maximo]);
        }

        return DB::transaction(function () use ($bl, $datos, $repetir, $usuario) {
            $bl = $this->blCompartido($bl);
            $bl->exigirEnRecepcion();

            $productos = $bl->detalles()->with('producto')->get()->pluck('producto', 'producto_id');
            $this->validarLineas($datos['lineas'], $productos);

            $pallet = filled($datos['pallet'] ?? null) ? $this->palletBloqueado($datos['pallet']) : null;

            $bultos = new Collection;

            for ($i = 0; $i < $repetir; $i++) {
                $bulto = Bulto::create([
                    'codigo'        => $this->consecutivos->codigoBulto(),
                    'bl_id'         => $bl->id,
                    'peso_bruto_kg' => Milesimas::texto(Milesimas::de($datos['peso_bruto_kg'])),
                ]);

                foreach ($datos['lineas'] as $linea) {
                    $bulto->agregarLinea($productos[(int) $linea['producto_id']], $linea['cantidad'])->save(); // dominio: RS-25, RS-29
                }

                if ($pallet) {
                    $pallet->agregarBulto($bulto);                              // dominio: mismo BL, pallet ABIERTO (D7)
                    $bulto->save();
                }

                $bultos->push($bulto);
            }

            $this->auditar($bl, $usuario, 'bultos registrados', [
                'bultos' => $bultos->pluck('codigo')->all(), 'pallet' => $pallet?->codigo,
            ]);

            return $bultos->load(['detalles.producto', 'pallet']);
        });
    }

    /** R9: si está en un pallet ABIERTO sale de él; con el pallet cerrado se rechaza (dominio). */
    public function anularBulto(Bulto $bulto, User $usuario): Bulto
    {
        return DB::transaction(function () use ($bulto, $usuario) {
            $bl = $this->blCompartido($bulto->bl);
            $bl->exigirEnRecepcion();

            $bulto = Bulto::query()->whereKey($bulto->getKey())->lockForUpdate()->firstOrFail();

            if ($bulto->pallet_id) {
                Pallet::query()->whereKey($bulto->pallet_id)->lockForUpdate()->first();
            }

            $bulto->anular();
            $bulto->save();

            $this->auditar($bl, $usuario, 'bulto anulado', ['bulto' => $bulto->codigo]);

            return $bulto->load(['detalles.producto', 'pallet']);
        });
    }

    /**
     * CUS-006 paso 5 (secuencia 3.3.1, D3, D5–D7, R10, R11). Una sola transacción:
     *  1. FOR UPDATE del BL.  2. Todo bulto no anulado en un pallet (D7).
     *  3. Pallets ABIERTOS: con bultos se cierran, vacíos se eliminan (R10).
     *  4. Discrepancia de bultos, unidades o peso fuera de tolerancia ⇒ observación (RS-33, D5, D6).
     *  5. Movimiento RECEPCION en CUARENTENA por cada línea: con la bodega del pallet si ya está UBICADO (D10).
     */
    public function finalizar(Bl $bl, ?string $observacion, User $usuario): Bl
    {
        return DB::transaction(function () use ($bl, $observacion, $usuario) {
            $bl = Bl::query()->whereKey($bl->getKey())->lockForUpdate()->firstOrFail();
            $bl->exigirEnRecepcion();

            $activos = Bulto::query()->where('bl_id', $bl->id)->where('estado', '!=', EstadoBulto::ANULADO->value);

            $sinPallet = (clone $activos)->whereNull('pallet_id')->orderBy('codigo')->pluck('codigo');

            if ($sinPallet->isNotEmpty()) {
                throw new ReglaNegocioException(
                    CodigoError::BULTOS_SIN_PALLET,
                    "Hay {$sinPallet->count()} bultos sin pallet. Ármelos antes de finalizar.",
                    ['bultos' => $sinPallet->take(20)->all(), 'total' => $sinPallet->count()],
                );
            }

            foreach (Pallet::query()->where('bl_id', $bl->id)->where('estado', EstadoPallet::ABIERTO->value)->lockForUpdate()->get() as $pallet) {
                if ($pallet->estaVacio()) {
                    $pallet->delete();
                } else {
                    $pallet->cerrar();
                    $pallet->save();
                }
            }

            $resumen = $this->resumen($bl);

            $bl->registrarDiscrepancia($resumen['bultos']['esperados'], $resumen['bultos']['recibidos']);
            $bl->finalizarRecepcion($observacion, hayNovedades: $resumen['novedades']);   // dominio: RS-33
            $bl->save();

            $lineas = BultoDetalle::query()
                ->whereIn('bulto_id', (clone $activos)->select('id'))
                ->with('bulto.pallet')
                ->orderBy('id')
                ->get();

            $referencia = mb_substr($bl->numero_bl, 0, 20);

            foreach ($lineas as $linea) {
                $pallet = $linea->bulto->pallet;
                $bodegaId = $pallet?->estado === EstadoPallet::UBICADO ? $pallet->bodega_id : null;

                $this->movimientos->registrar(
                    TipoMovimiento::RECEPCION, $linea, $linea->cantidad_recibida, $bodegaId, EstadoLegal::CUARENTENA, $usuario, $referencia,
                );
            }

            $this->auditar($bl, $usuario, 'recepción finalizada', [
                'bultos' => $resumen['bultos'], 'peso_kg' => $resumen['peso_kg'], 'lineas' => $lineas->count(),
            ]);

            return $bl;
        });
    }

    /**
     * Totales recibido vs. esperado, calculados en el servidor (maqueta 3.2.1: la app no calcula nada).
     * No cuenta bultos anulados.
     */
    public function resumen(Bl $bl): array
    {
        $bl->loadMissing('cliente:id,codigo_corto,razon_social');
        $detalles = $bl->detalles()->with('producto:id,sku,descripcion,unidad_medida')->orderBy('id')->get();

        $activos = Bulto::query()->where('bl_id', $bl->id)->where('estado', '!=', EstadoBulto::ANULADO->value);

        $bultos = (clone $activos)->toBase()
            ->selectRaw('COUNT(*) AS total, COUNT(*) FILTER (WHERE pallet_id IS NULL) AS sin_pallet, COALESCE(SUM(peso_bruto_kg), 0) AS peso')
            ->first();

        $recibidas = BultoDetalle::query()
            ->whereIn('bulto_id', (clone $activos)->select('id'))
            ->groupBy('producto_id')
            ->toBase()
            ->selectRaw('producto_id, SUM(cantidad_recibida) AS cantidad')
            ->pluck('cantidad', 'producto_id');

        $pallets = Pallet::query()->where('bl_id', $bl->id)->toBase()
            ->selectRaw('estado, COUNT(*) AS n')->groupBy('estado')->pluck('n', 'estado');

        $productos = [];
        $unidadesEsperadas = 0;
        $unidadesRecibidas = 0;
        $diferenciaUnidades = false;

        foreach ($detalles as $d) {
            $esperadas = Milesimas::de($d->unidades_esperadas);
            $recibida = Milesimas::de((string) ($recibidas[$d->producto_id] ?? '0'));

            $unidadesEsperadas += $esperadas;
            $unidadesRecibidas += $recibida;
            $diferenciaUnidades = $diferenciaUnidades || $esperadas !== $recibida;

            $productos[] = [
                'producto_id' => $d->producto_id,
                'sku'         => $d->producto->sku,
                'descripcion' => $d->producto->descripcion,
                'unidad'      => $d->producto->unidad_medida->value,
                'esperadas'   => Milesimas::texto($esperadas),
                'recibidas'   => Milesimas::texto($recibida),
                'diferencia'  => Milesimas::texto($recibida - $esperadas),
            ];
        }

        $bultosEsperados = (int) $detalles->sum('bultos_esperados');
        $bultosRecibidos = (int) $bultos->total;

        $pesoEsperado = $detalles->sum(fn ($d) => Milesimas::de($d->peso_bruto_kg));
        $pesoRecibido = Milesimas::de((string) $bultos->peso);
        $tolerancia = Milesimas::de($this->configuracion->decimal('tolerancia_peso'));   // % en milésimas (5 % = 5000)

        // |recibido − esperado| ≤ esperado × tolerancia %   (todo en enteros)
        $dentroDeTolerancia = abs($pesoRecibido - $pesoEsperado) * 100_000 <= $pesoEsperado * $tolerancia;

        $novedades = $diferenciaUnidades || ! $dentroDeTolerancia;

        return [
            'bl' => [
                'id'        => $bl->id,
                'numero_bl' => $bl->numero_bl,
                'estado'    => $bl->estado->value,
                'cliente'   => ['codigo' => $bl->cliente->codigo_corto, 'razon_social' => $bl->cliente->razon_social],
            ],
            'bultos' => [
                'recibidos'  => $bultosRecibidos,
                'esperados'  => $bultosEsperados,
                'diferencia' => $bultosRecibidos - $bultosEsperados,
                'sin_pallet' => (int) $bultos->sin_pallet,
            ],
            'unidades' => [
                'recibidas' => Milesimas::texto($unidadesRecibidas),
                'esperadas' => Milesimas::texto($unidadesEsperadas),
            ],
            'peso_kg' => [
                'recibido'             => Milesimas::texto($pesoRecibido),
                'esperado'             => Milesimas::texto($pesoEsperado),
                'diferencia'           => Milesimas::texto($pesoRecibido - $pesoEsperado),
                'tolerancia_pct'       => $this->configuracion->decimal('tolerancia_peso'),
                'dentro_de_tolerancia' => $dentroDeTolerancia,
            ],
            'pallets' => [
                'abiertos' => (int) ($pallets[EstadoPallet::ABIERTO->value] ?? 0),
                'cerrados' => (int) ($pallets[EstadoPallet::CERRADO->value] ?? 0),
                'ubicados' => (int) ($pallets[EstadoPallet::UBICADO->value] ?? 0),
            ],
            'productos'            => $productos,
            'novedades'            => $novedades,
            'requiere_observacion' => $novedades || $bultosRecibidos !== $bultosEsperados,
            'puede_finalizar'      => $bl->estado === EstadoBl::EN_RECEPCION && (int) $bultos->sin_pallet === 0,
        ];
    }

    // ---------------- Apoyo ----------------

    /** FOR SHARE: varios operarios a la vez; bloquea solo frente al FOR UPDATE de finalizar (D8). */
    private function blCompartido(Bl $bl): Bl
    {
        return Bl::query()->whereKey($bl->getKey())->sharedLock()->firstOrFail();
    }

    private function palletBloqueado(string $codigo): Pallet
    {
        $pallet = Pallet::query()->where('codigo', $codigo)->lockForUpdate()->first();

        if (! $pallet) {
            throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, "No existe el pallet {$codigo}.", ['codigo' => $codigo]);
        }

        return $pallet;
    }

    /** RS-25 (producto del manifiesto) y R5 (UND solo enteros; KG y LT hasta 3 decimales). */
    private function validarLineas(array $lineas, \Illuminate\Support\Collection $productos): void
    {
        foreach ($lineas as $linea) {
            $producto = $productos->get((int) $linea['producto_id']);

            if (! $producto) {
                throw new ReglaNegocioException(CodigoError::PRODUCTO_NO_EN_BL, datos: ['producto_id' => (int) $linea['producto_id']]);
            }

            if ($producto->unidad_medida->value === 'UND' && Milesimas::de($linea['cantidad']) % 1000 !== 0) {
                throw new ReglaNegocioException(
                    CodigoError::CANTIDAD_INVALIDA,
                    "El producto {$producto->sku} se maneja en unidades enteras (UND).",
                    ['sku' => $producto->sku, 'cantidad' => $linea['cantidad']],
                );
            }
        }
    }

    private function auditar(Bl $bl, User $usuario, string $evento, array $props = []): void
    {
        activity('inbound')->performedOn($bl)->causedBy($usuario)
            ->withProperties($props + ['ip' => request()->ip()])
            ->log($evento);
    }
}

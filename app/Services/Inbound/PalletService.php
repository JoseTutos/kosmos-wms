<?php

namespace App\Services\Inbound;

use App\Enums\CodigoError;
use App\Enums\EstadoPallet;
use App\Exceptions\ReglaNegocioException;
use App\Models\Bl;
use App\Models\Bulto;
use App\Models\Pallet;
use App\Models\User;
use App\Services\Nucleo\ConsecutivoService;
use Illuminate\Support\Facades\DB;

/**
 * CUS-006: armar pallets por escaneo (D7, D7a). Toda operación exige el BL EN_RECEPCION
 * (después de finalizar, el pallet solo se ubica: CUS-008).
 * Concurrencia (D8): BL con FOR SHARE y el pallet con FOR UPDATE: dos operarios sobre pallets distintos no se esperan.
 */
final class PalletService
{
    public function __construct(private readonly ConsecutivoService $consecutivos) {}

    public function crear(Bl $bl, User $usuario): Pallet
    {
        return DB::transaction(function () use ($bl, $usuario) {
            $bl = $this->blEnRecepcion($bl->getKey());

            $pallet = Pallet::create(['codigo' => $this->consecutivos->codigoPallet(), 'bl_id' => $bl->id]);
            $this->auditar($pallet, $usuario, 'pallet creado');

            return $pallet->refresh();
        });
    }

    /** Escaneo del bulto sobre el pallet. Si el bulto estaba en otro pallet, hay que retirarlo primero. */
    public function agregar(Pallet $pallet, string $codigoBulto, User $usuario): Pallet
    {
        return DB::transaction(function () use ($pallet, $codigoBulto, $usuario) {
            $pallet = $this->palletBloqueado($pallet);
            $bulto = $this->bultoBloqueado($codigoBulto);

            $pallet->agregarBulto($bulto);                                      // dominio: mismo BL, ABIERTO, no anulado
            $bulto->save();

            $this->auditar($pallet, $usuario, 'bulto agregado al pallet', ['bulto' => $bulto->codigo]);

            return $pallet;
        });
    }

    /** P3 / FA-02: solo con el pallet ABIERTO. */
    public function retirar(Pallet $pallet, string $codigoBulto, User $usuario): Pallet
    {
        return DB::transaction(function () use ($pallet, $codigoBulto, $usuario) {
            $pallet = $this->palletBloqueado($pallet);
            $bulto = $this->bultoBloqueado($codigoBulto);

            $pallet->retirarBulto($bulto);
            $bulto->save();

            $this->auditar($pallet, $usuario, 'bulto retirado del pallet', ['bulto' => $bulto->codigo]);

            return $pallet;
        });
    }

    public function cerrar(Pallet $pallet, User $usuario): Pallet
    {
        return DB::transaction(function () use ($pallet, $usuario) {
            $pallet = $this->palletBloqueado($pallet);

            $pallet->cerrar();                                                  // dominio: no vacío
            $pallet->save();

            $this->auditar($pallet, $usuario, 'pallet cerrado');

            return $pallet;
        });
    }

    /** P2: solo si no está UBICADO (dominio: CERRADO → ABIERTO). */
    public function reabrir(Pallet $pallet, User $usuario): Pallet
    {
        return DB::transaction(function () use ($pallet, $usuario) {
            $pallet = $this->palletBloqueado($pallet);

            $pallet->reabrir();
            $pallet->save();

            $this->auditar($pallet, $usuario, 'pallet reabierto');

            return $pallet;
        });
    }

    /** P4: ABIERTO y sin bultos. El código PLT no se reutiliza. */
    public function eliminar(Pallet $pallet, User $usuario): void
    {
        DB::transaction(function () use ($pallet, $usuario) {
            $pallet = $this->palletBloqueado($pallet);

            if ($pallet->estado !== EstadoPallet::ABIERTO || $pallet->bultos()->exists()) {
                throw new ReglaNegocioException(
                    CodigoError::PALLET_ESTADO_INVALIDO,
                    "Solo se elimina un pallet abierto y sin bultos. Retire los bultos de {$pallet->codigo} primero.",
                    ['pallet' => $pallet->codigo],
                );
            }

            $this->auditar($pallet, $usuario, 'pallet eliminado', ['codigo' => $pallet->codigo]);
            $pallet->delete();
        });
    }

    // ---------------- Apoyo ----------------

    private function blEnRecepcion(int $blId): Bl
    {
        $bl = Bl::query()->whereKey($blId)->sharedLock()->firstOrFail();
        $bl->exigirEnRecepcion();

        return $bl;
    }

    private function palletBloqueado(Pallet $pallet): Pallet
    {
        $this->blEnRecepcion($pallet->bl_id);

        return Pallet::query()->whereKey($pallet->getKey())->lockForUpdate()->firstOrFail();
    }

    private function bultoBloqueado(string $codigo): Bulto
    {
        $bulto = Bulto::query()->where('codigo', $codigo)->lockForUpdate()->first();

        if (! $bulto) {
            throw new ReglaNegocioException(CodigoError::CODIGO_NO_ENCONTRADO, "No existe el bulto {$codigo}.", ['codigo' => $codigo]);
        }

        return $bulto;
    }

    private function auditar(Pallet $pallet, User $usuario, string $evento, array $props = []): void
    {
        activity('inbound')->performedOn($pallet)->causedBy($usuario)
            ->withProperties($props + ['pallet' => $pallet->codigo, 'ip' => request()->ip()])
            ->log($evento);
    }
}

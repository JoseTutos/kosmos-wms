<?php

namespace App\Policies;

use App\Enums\Rol;
use App\Models\Bl;
use App\Models\User;
use App\Services\Inbound\BlService;
use Illuminate\Auth\Access\Response;

/** CUS-005: solo el SUPERVISOR registra BL (B1). Editar y eliminar solo en PROGRAMADO (FA-03, B2). */
class BlPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tieneRol(Rol::SUPERVISOR);
    }

    public function view(User $user, Bl $bl): bool
    {
        return $user->tieneRol(Rol::SUPERVISOR);
    }

    public function create(User $user): bool
    {
        return $user->tieneRol(Rol::SUPERVISOR);
    }

    public function update(User $user, Bl $bl): Response
    {
        if (! $user->tieneRol(Rol::SUPERVISOR)) {
            return Response::deny('Su rol no tiene permiso para esta acción.');
        }

        return $bl->esEditable()
            ? Response::allow()
            : Response::deny("El BL {$bl->numero_bl} ya no está PROGRAMADO: no se puede editar.");
    }

    public function delete(User $user, Bl $bl): Response
    {
        $edicion = $this->update($user, $bl);

        if ($edicion->denied()) {
            return $edicion;
        }

        return self::tieneRecepcion($bl)
            ? Response::deny("El BL {$bl->numero_bl} ya tiene bultos o pallets: no se puede eliminar.")
            : Response::allow();
    }

    /** Usa withExists(['bultos', 'pallets']) si el listado ya lo cargó (evita 2 consultas por fila). */
    private static function tieneRecepcion(Bl $bl): bool
    {
        $atributos = $bl->getAttributes();

        $bultos = array_key_exists('bultos_exists', $atributos) ? (bool) $atributos['bultos_exists'] : $bl->bultos()->exists();
        $pallets = array_key_exists('pallets_exists', $atributos) ? (bool) $atributos['pallets_exists'] : $bl->pallets()->exists();

        return $bultos || $pallets;
    }

    /** B6: fecha y acta de preinspección editables hasta que el BL quede RECIBIDO. */
    public function preinspeccion(User $user, Bl $bl): Response
    {
        if (! $user->tieneRol(Rol::SUPERVISOR)) {
            return Response::deny('Su rol no tiene permiso para esta acción.');
        }

        return BlService::admitePreinspeccion($bl)
            ? Response::allow()
            : Response::deny('La preinspección solo se modifica antes de que el BL quede RECIBIDO.');
    }
}

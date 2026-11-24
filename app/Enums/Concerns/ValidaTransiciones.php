<?php

namespace App\Enums\Concerns;

use App\Enums\Contracts\MaquinaEstados;

trait ValidaTransiciones
{
    public function puedePasarA(MaquinaEstados $destino): bool
    {
        return in_array($destino, $this->transicionesPermitidas(), true);
    }

    public function esFinal(): bool
    {
        return $this->transicionesPermitidas() === [];
    }
}

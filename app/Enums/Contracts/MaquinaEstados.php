<?php

namespace App\Enums\Contracts;

use BackedEnum;

/** Enum de estado con transiciones válidas (diagramas de estado 3.1.2). */
interface MaquinaEstados extends BackedEnum
{
    /** @return array<int, static> */
    public function transicionesPermitidas(): array;

    public function puedePasarA(MaquinaEstados $destino): bool;

    public function esFinal(): bool;
}

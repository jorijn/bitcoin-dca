<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Command;

interface MachineReadableOutputCommandInterface
{
    public function isDisplayingMachineReadableOutput(): bool;
}

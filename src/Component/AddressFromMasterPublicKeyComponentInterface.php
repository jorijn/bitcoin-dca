<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Component;

interface AddressFromMasterPublicKeyComponentInterface
{
    public function derive(string $masterPublicKey, $path = '0/0'): string;

    public function supported(): bool;
}

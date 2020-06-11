<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service;

interface BalanceServiceInterface
{
    public function supportsExchange(string $exchange): bool;

    public function getBalances(): array;
}

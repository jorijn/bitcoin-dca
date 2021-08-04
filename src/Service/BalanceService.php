<?php

declare(strict_types=1);

/*
 * This file is part of the Bitcoin-DCA package.
 *
 * (c) Jorijn Schrijvershof <jorijn@jorijn.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jorijn\Bitcoin\Dca\Service;

use Jorijn\Bitcoin\Dca\Exception\NoExchangeAvailableException;

class BalanceService
{
    /** @var BalanceServiceInterface[] */
    protected iterable $registeredServices;
    protected string $configuredExchange;

    public function __construct(iterable $registeredServices, string $configuredExchange)
    {
        $this->registeredServices = $registeredServices;
        $this->configuredExchange = $configuredExchange;
    }

    public function getBalances(): array
    {
        foreach ($this->registeredServices as $registeredService) {
            if ($registeredService->supportsExchange($this->configuredExchange)) {
                return $registeredService->getBalances();
            }
        }

        throw new NoExchangeAvailableException('no exchange was available to provide balances');
    }
}

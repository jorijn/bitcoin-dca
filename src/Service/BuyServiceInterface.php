<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service;

use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;

interface BuyServiceInterface
{
    /**
     * Should return true or false depending on if this service will support provided exchange name.
     */
    public function supportsExchange(string $exchange): bool;

    /**
     * Method should buy $amount of $baseCurrency in BTC. Should only return a CompletedBuyOrder object when the
     * (market) order was filled. Should throw BuyTimeoutException if it cannot be filled within $timeout.
     *
     * @param int    $amount       the amount that should be bought
     * @param string $baseCurrency the base currency this buy should execute in
     * @param int    $timeout      timeout in seconds
     */
    public function buy(int $amount, string $baseCurrency, int $timeout): CompletedBuyOrder;
}

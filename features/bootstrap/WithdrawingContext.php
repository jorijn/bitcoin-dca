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

namespace Features\Jorijn\Bitcoin\Dca;

use Behat\Behat\Context\Context;
use Jorijn\Bitcoin\Dca\Service\MockExchange\MockExchangeWithdrawService;

class WithdrawingContext implements Context
{
    public function __construct(protected MockExchangeWithdrawService $withdrawService)
    {
    }

    /**
     * @Given /^the balance on the exchange is (\d+) satoshis$/
     */
    public function theBalanceOnTheExchangeIsSatoshis(int $satoshis): void
    {
        $this->withdrawService->setAvailableBalance($satoshis);
    }

    /**
     * @Given /^the withdrawal fee on the exchange is going to be (\d+) satoshis$/
     */
    public function theWithdrawalFeeOnTheExchangeIsGoingToBeSatoshis(int $satoshis): void
    {
        $this->withdrawService->setWithdrawFeeInSatoshis($satoshis);
    }

    /**
     * @Given /^I expect the balance of the exchange to be (\d+) satoshis$/
     */
    public function iExpectTheBalanceOfTheExchangeToBeSatoshis(int $satoshis): void
    {
        $balance = $this->withdrawService->getAvailableBalance();
        \assert($satoshis === $balance, sprintf('available balance is not %d, actual: %d', $satoshis, $balance));
    }
}

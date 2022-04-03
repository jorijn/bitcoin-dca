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
use Jorijn\Bitcoin\Dca\Service\MockExchange\MockExchangeBuyService;

class BuyingContext implements Context
{
    public function __construct(protected MockExchangeBuyService $buyService)
    {
    }

    /**
     * @When /^the current Bitcoin price is (\d+) dollar$/
     */
    public function theCurrentBitcoinPriceIsDollar(int $price): void
    {
        $this->buyService->setBitcoinPrice($price);
    }

    /**
     * @Given /^the buying fee will be (\d+\.\d+) ([A-Z]{3})$/
     */
    public function theCurrentFeeIsBTC(string $feeAmount, string $feeCurrency): void
    {
        $this->buyService->setFeeAmount($feeAmount);
        $this->buyService->setFeeCurrency($feeCurrency);
    }
}

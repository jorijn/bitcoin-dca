<?php

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

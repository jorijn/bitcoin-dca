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

namespace Jorijn\Bitcoin\Dca\Service\MockExchange;

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyServiceInterface;

class MockExchangeBuyService implements BuyServiceInterface
{
    protected bool $isEnabled;
    protected string $baseCurrency;
    private int $bitcoinPrice;
    private string $feeAmount;
    private string $feeCurrency;

    public function __construct(bool $isEnabled, string $baseCurrency)
    {
        $this->isEnabled = $isEnabled;
        $this->baseCurrency = $baseCurrency;
        $this->setBitcoinPrice(random_int(10000, 50000));
        $this->setFeeAmount(bcdiv((string) random_int(100, 200), Bitcoin::SATOSHIS, Bitcoin::DECIMALS));
        $this->setFeeCurrency('BTC');
    }

    public function setBitcoinPrice(int $bitcoinPrice): self
    {
        $this->bitcoinPrice = $bitcoinPrice;

        return $this;
    }

    public function setFeeAmount(string $feeAmount): self
    {
        $this->feeAmount = $feeAmount;

        return $this;
    }

    public function setFeeCurrency(string $feeCurrency): self
    {
        $this->feeCurrency = $feeCurrency;

        return $this;
    }

    public function supportsExchange(string $exchange): bool
    {
        return $this->isEnabled;
    }

    public function initiateBuy(int $amount): CompletedBuyOrder
    {
        return $this->createRandomBuyOrder($amount);
    }

    public function checkIfOrderIsFilled(string $orderId): CompletedBuyOrder
    {
        // not implemented right now
    }

    public function cancelBuyOrder(string $orderId): void
    {
        // void method, always succeeds
    }

    protected function createRandomBuyOrder(int $amount): CompletedBuyOrder
    {
        $bitcoinBought = bcdiv((string) $amount, (string) $this->bitcoinPrice, Bitcoin::DECIMALS);
        $satoshisBought = bcmul($bitcoinBought, Bitcoin::SATOSHIS, Bitcoin::DECIMALS);

        return (new CompletedBuyOrder())
            ->setAmountInSatoshis((int) $satoshisBought)
            ->setFeesInSatoshis(
                'BTC' === $this->feeCurrency ? (int) bcmul($this->feeAmount, Bitcoin::SATOSHIS, Bitcoin::DECIMALS) : 0
            )
            ->setDisplayAmountBought($bitcoinBought.' BTC')
            ->setDisplayAveragePrice($this->bitcoinPrice.' '.$this->baseCurrency)
            ->setDisplayAmountSpent((string) $amount)
            ->setDisplayFeesSpent($this->feeAmount.' '.$this->feeCurrency)
            ->setDisplayAmountSpentCurrency($this->baseCurrency)
        ;
    }
}

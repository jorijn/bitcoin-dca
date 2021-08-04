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

namespace Jorijn\Bitcoin\Dca\Model;

class CompletedBuyOrder
{
    private int $amountInSatoshis = 0;
    private int $feesInSatoshis = 0;
    private \DateTimeInterface $purchaseMadeAt;

    private ?string $displayAmountBought;
    private ?string $displayAmountSpent;
    private ?string $displayAmountSpentCurrency;
    private ?string $displayAveragePrice;
    private ?string $displayFeesSpent;

    public function getDisplayAmountSpentCurrency(): ?string
    {
        return $this->displayAmountSpentCurrency;
    }

    public function setDisplayAmountSpentCurrency(?string $displayAmountSpentCurrency): self
    {
        $this->displayAmountSpentCurrency = $displayAmountSpentCurrency;

        return $this;
    }

    public function __construct()
    {
        $this->purchaseMadeAt = new \DateTimeImmutable();
    }

    public function getPurchaseMadeAt(): string
    {
        return $this->purchaseMadeAt->format(\DateTimeInterface::ATOM);
    }

    public function getAmountInSatoshis(): int
    {
        return $this->amountInSatoshis;
    }

    public function setAmountInSatoshis(int $amountInSatoshis): self
    {
        $this->amountInSatoshis = $amountInSatoshis;

        return $this;
    }

    public function getFeesInSatoshis(): int
    {
        return $this->feesInSatoshis;
    }

    public function setFeesInSatoshis(int $feesInSatoshis): self
    {
        $this->feesInSatoshis = $feesInSatoshis;

        return $this;
    }

    public function getDisplayAmountBought(): ?string
    {
        return $this->displayAmountBought;
    }

    public function setDisplayAmountBought(?string $displayAmountBought): self
    {
        $this->displayAmountBought = $displayAmountBought;

        return $this;
    }

    public function getDisplayAmountSpent(): ?string
    {
        return $this->displayAmountSpent;
    }

    public function setDisplayAmountSpent(?string $displayAmountSpent): self
    {
        $this->displayAmountSpent = $displayAmountSpent;

        return $this;
    }

    public function getDisplayAveragePrice(): ?string
    {
        return $this->displayAveragePrice;
    }

    public function setDisplayAveragePrice(?string $displayAveragePrice): self
    {
        $this->displayAveragePrice = $displayAveragePrice;

        return $this;
    }

    public function getDisplayFeesSpent(): ?string
    {
        return $this->displayFeesSpent;
    }

    public function setDisplayFeesSpent(?string $displayFeesSpent): self
    {
        $this->displayFeesSpent = $displayFeesSpent;

        return $this;
    }
}

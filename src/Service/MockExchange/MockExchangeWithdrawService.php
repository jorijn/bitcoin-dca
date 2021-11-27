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

use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Service\WithdrawServiceInterface;

/**
 * @codeCoverageIgnore This file is solely used for testing
 */
class MockExchangeWithdrawService implements WithdrawServiceInterface
{
    protected bool $isEnabled;
    protected int $availableBalance;
    protected int $withdrawFeeInSatoshis;

    public function __construct(bool $isEnable)
    {
        $this->isEnabled = $isEnable;

        $this->setAvailableBalance(random_int(100000, 500000));
        $this->setWithdrawFeeInSatoshis(random_int(30000, 50000));
    }

    public function withdraw(int $balanceToWithdraw, string $addressToWithdrawTo): CompletedWithdraw
    {
        return new CompletedWithdraw($addressToWithdrawTo, $balanceToWithdraw, sprintf('MOCK_%s', time()));
    }

    public function getAvailableBalance(): int
    {
        return $this->availableBalance;
    }

    public function getWithdrawFeeInSatoshis(): int
    {
        return $this->withdrawFeeInSatoshis;
    }

    public function supportsExchange(string $exchange): bool
    {
        return $this->isEnabled;
    }

    public function setAvailableBalance(int $availableBalance): self
    {
        $this->availableBalance = $availableBalance;

        return $this;
    }

    public function setWithdrawFeeInSatoshis(int $withdrawFeeInSatoshis): self
    {
        $this->withdrawFeeInSatoshis = $withdrawFeeInSatoshis;

        return $this;
    }
}

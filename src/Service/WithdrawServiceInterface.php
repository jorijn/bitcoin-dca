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

use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;

interface WithdrawServiceInterface
{
    public function withdraw(int $balanceToWithdraw, string $addressToWithdrawTo): CompletedWithdraw;

    public function getAvailableBalance(): int;

    public function getWithdrawFeeInSatoshis(): int;

    public function supportsExchange(string $exchange): bool;
}

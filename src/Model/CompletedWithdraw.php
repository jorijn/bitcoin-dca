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

class CompletedWithdraw
{
    protected string $id;
    protected string $recipientAddress;
    protected int $netAmount;

    public function __construct(string $recipientAddress, int $netAmount, string $id)
    {
        $this->id = $id;
        $this->recipientAddress = $recipientAddress;
        $this->netAmount = $netAmount;
    }

    public function getRecipientAddress(): string
    {
        return $this->recipientAddress;
    }

    public function getNetAmount(): int
    {
        return $this->netAmount;
    }

    public function getId(): string
    {
        return $this->id;
    }
}

<?php

declare(strict_types=1);

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

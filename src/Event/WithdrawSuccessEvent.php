<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Event;

use Symfony\Contracts\EventDispatcher\Event;

class WithdrawSuccessEvent extends Event
{
    protected string $address;
    protected int $amountInSatoshis;
    protected array $context;

    public function __construct(string $address, int $amountInSatoshis, array $context = [])
    {
        $this->address = $address;
        $this->amountInSatoshis = $amountInSatoshis;
        $this->context = $context;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getAmountInSatoshis(): int
    {
        return $this->amountInSatoshis;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}

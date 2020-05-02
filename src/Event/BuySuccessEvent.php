<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Event;

class BuySuccessEvent
{
    protected int $amount;
    protected ?string $tag;

    public function __construct(int $amount, string $tag = null)
    {
        $this->amount = $amount;
        $this->tag = $tag;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }
}

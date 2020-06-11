<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Event;

use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Symfony\Contracts\EventDispatcher\Event;

class BuySuccessEvent extends Event
{
    protected CompletedBuyOrder $buyOrder;
    protected ?string $tag;

    public function __construct(CompletedBuyOrder $buyOrder, string $tag = null)
    {
        $this->tag = $tag;
        $this->buyOrder = $buyOrder;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function getBuyOrder(): CompletedBuyOrder
    {
        return $this->buyOrder;
    }
}

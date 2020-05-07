<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Event;

use Jorijn\Bl3pDca\Model\CompletedWithdraw;
use Symfony\Contracts\EventDispatcher\Event;

class WithdrawSuccessEvent extends Event
{
    protected ?string $tag;
    protected CompletedWithdraw $completedWithdraw;

    public function __construct(CompletedWithdraw $completedWithdraw, string $tag = null)
    {
        $this->tag = $tag;
        $this->completedWithdraw = $completedWithdraw;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function getCompletedWithdraw(): CompletedWithdraw
    {
        return $this->completedWithdraw;
    }
}

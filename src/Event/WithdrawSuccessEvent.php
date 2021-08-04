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

namespace Jorijn\Bitcoin\Dca\Event;

use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
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

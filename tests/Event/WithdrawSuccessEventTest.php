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

namespace Tests\Jorijn\Bitcoin\Dca\Event;

use Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent
 *
 * @internal
 */
final class WithdrawSuccessEventTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getCompletedWithdraw
     * @covers ::getTag
     */
    public function testGetters(): void
    {
        $address = 'a'.random_int(1000, 2000);
        $amount = random_int(1000, 2000);
        $id = (string) random_int(1000, 2000);
        $tag = 'tag'.random_int(1000, 2000);

        $completedWithdraw = new CompletedWithdraw($address, $amount, $id);
        $withdrawSuccessEvent = new WithdrawSuccessEvent($completedWithdraw, $tag);

        static::assertSame($completedWithdraw, $withdrawSuccessEvent->getCompletedWithdraw());
        static::assertSame($tag, $withdrawSuccessEvent->getTag());
    }
}

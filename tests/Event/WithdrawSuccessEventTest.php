<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Event;

use Jorijn\Bl3pDca\Event\WithdrawSuccessEvent;
use Jorijn\Bl3pDca\Model\CompletedWithdraw;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Event\WithdrawSuccessEvent
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
        $event = new WithdrawSuccessEvent($completedWithdraw, $tag);

        static::assertSame($completedWithdraw, $event->getCompletedWithdraw());
        static::assertSame($tag, $event->getTag());
    }
}

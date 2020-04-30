<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Event;

use Jorijn\Bl3pDca\Event\WithdrawSuccessEvent;
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
     * @covers ::getAddress
     * @covers ::getAmountInSatoshis
     * @covers ::getContext
     */
    public function testGetters(): void
    {
        $address = 'a'.mt_rand();
        $amount = mt_rand();
        $context = ['c'.mt_rand()];

        $event = new WithdrawSuccessEvent($address, $amount, $context);

        static::assertSame($address, $event->getAddress());
        static::assertSame($amount, $event->getAmountInSatoshis());
        static::assertSame($context, $event->getContext());
    }
}

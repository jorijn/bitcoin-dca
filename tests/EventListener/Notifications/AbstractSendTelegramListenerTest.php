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

namespace Tests\Jorijn\Bitcoin\Dca\EventListener\Notifications;

use Jorijn\Bitcoin\Dca\EventListener\Notifications\AbstractSendTelegramListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramTransport;

/**
 * @internal
 *
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\Notifications\AbstractSendTelegramListener
 */
final class AbstractSendTelegramListenerTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getDispatcher
     * @covers ::getExchange
     * @covers ::getTransport
     * @covers ::isEnabled
     */
    public function testGetterAndSetters(): void
    {
        $listener = $this->getMockForAbstractClass(AbstractSendTelegramListener::class, [
            $telegramTransport = new TelegramTransport(''),
            $eventDispatcher = new EventDispatcher(),
            $exchange = 'e'.random_int(1000, 2000),
            $enabled = (bool) random_int(0, 1),
        ]);

        static::assertSame($telegramTransport, $listener->getTransport());
        static::assertSame($eventDispatcher, $listener->getDispatcher());
        static::assertSame($exchange, $listener->getExchange());
        static::assertSame($enabled, $listener->isEnabled());
    }
}

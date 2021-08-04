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

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Event\BuySuccessEvent
 *
 * @internal
 */
final class BuySuccessEventTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getBuyOrder
     * @covers ::getTag
     */
    public function testGetters(): void
    {
        $dto = new CompletedBuyOrder();
        $tag = 'tag'.random_int(1000, 2000);

        $event = new BuySuccessEvent($dto, $tag);

        static::assertSame($dto, $event->getBuyOrder());
        static::assertSame($tag, $event->getTag());
    }
}

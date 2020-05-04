<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Event;

use Jorijn\Bl3pDca\Event\BuySuccessEvent;
use Jorijn\Bl3pDca\Model\CompletedBuyOrder;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Event\BuySuccessEvent
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

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

namespace Tests\Jorijn\Bitcoin\Dca\Model;

use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder
 *
 * @internal
 */
final class CompletedBuyOrderTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getAmountInSatoshis
     * @covers ::setAmountInSatoshis
     * @covers ::getFeesInSatoshis
     * @covers ::setFeesInSatoshis
     * @covers ::getDisplayAmountBought
     * @covers ::setDisplayAmountBought
     * @covers ::getDisplayAmountSpent
     * @covers ::setDisplayAmountSpent
     * @covers ::getDisplayAmountSpentCurrency
     * @covers ::setDisplayAmountSpentCurrency
     * @covers ::getDisplayAveragePrice
     * @covers ::setDisplayAveragePrice
     * @covers ::getDisplayFeesSpent
     * @covers ::setDisplayFeesSpent
     * @covers ::getPurchaseMadeAt
     */
    public function testGettersAndSetters(): void
    {
        $completedBuyOrder = new CompletedBuyOrder();

        $completedBuyOrder
            ->setAmountInSatoshis($amountInSatoshis = random_int(1000, 2000))
            ->setFeesInSatoshis($feesInSatoshis = random_int(1000, 2000))
            ->setDisplayFeesSpent($feesSpent = '0.'.random_int(1000, 2000).' BTC')
            ->setDisplayAveragePrice($averagePrice = '€'.random_int(1000, 2000))
            ->setDisplayAmountSpent($amountSpent = '€'.random_int(1000, 2000))
            ->setDisplayAmountSpentCurrency($currency = 'EUR')
            ->setDisplayAmountBought($amountBought = random_int(1000, 2000).' BTC')
        ;

        static::assertSame($amountInSatoshis, $completedBuyOrder->getAmountInSatoshis());
        static::assertSame($feesInSatoshis, $completedBuyOrder->getFeesInSatoshis());
        static::assertSame($feesSpent, $completedBuyOrder->getDisplayFeesSpent());
        static::assertSame($averagePrice, $completedBuyOrder->getDisplayAveragePrice());
        static::assertSame($amountSpent, $completedBuyOrder->getDisplayAmountSpent());
        static::assertSame($currency, $completedBuyOrder->getDisplayAmountSpentCurrency());
        static::assertSame($amountBought, $completedBuyOrder->getDisplayAmountBought());

        static::assertEqualsWithDelta(
            new \DateTimeImmutable(),
            \DateTimeImmutable::createFromFormat(
                \DateTimeInterface::ATOM,
                $completedBuyOrder->getPurchaseMadeAt()
            ),
            10
        );
    }
}

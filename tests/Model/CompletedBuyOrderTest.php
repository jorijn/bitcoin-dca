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
     * @covers ::<public>
     */
    public function testGettersAndSetters(): void
    {
        $dto = new CompletedBuyOrder();

        $dto
            ->setAmountInSatoshis($amountInSatoshis = random_int(1000, 2000))
            ->setFeesInSatoshis($feesInSatoshis = random_int(1000, 2000))
            ->setDisplayFeesSpent($feesSpent = '0.'.random_int(1000, 2000).' BTC')
            ->setDisplayAveragePrice($averagePrice = '€'.random_int(1000, 2000))
            ->setDisplayAmountSpent($amountSpent = '€'.random_int(1000, 2000))
            ->setDisplayAmountBought($amountBought = random_int(1000, 2000).' BTC')
        ;

        static::assertSame($amountInSatoshis, $dto->getAmountInSatoshis());
        static::assertSame($feesInSatoshis, $dto->getFeesInSatoshis());
        static::assertSame($feesSpent, $dto->getDisplayFeesSpent());
        static::assertSame($averagePrice, $dto->getDisplayAveragePrice());
        static::assertSame($amountSpent, $dto->getDisplayAmountSpent());
        static::assertSame($amountBought, $dto->getDisplayAmountBought());

        static::assertEqualsWithDelta(
            new \DateTimeImmutable(),
            \DateTimeImmutable::createFromFormat(
                \DateTimeInterface::ATOM,
                $dto->getPurchaseMadeAt()
            ),
            10
        );
    }
}

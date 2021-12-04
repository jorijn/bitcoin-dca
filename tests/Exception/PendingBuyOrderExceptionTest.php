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

namespace Tests\Jorijn\Bitcoin\Dca\Exception;

use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException
 *
 * @internal
 */
final class PendingBuyOrderExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getOrderId
     */
    public function testGetOrderId(): void
    {
        $orderId = 'oid'.random_int(1000, 2000);
        $pendingBuyOrderException = new PendingBuyOrderException($orderId);
        static::assertSame($orderId, $pendingBuyOrderException->getOrderId());
    }
}

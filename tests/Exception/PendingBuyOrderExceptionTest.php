<?php

declare(strict_types=1);

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
     * @covers ::getOrderId
     */
    public function testGetOrderId(): void
    {
        $orderId = 'oid'.random_int(1000, 2000);
        $exception = new PendingBuyOrderException($orderId);
        static::assertSame($orderId, $exception->getOrderId());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Model;

use Jorijn\Bl3pDca\Model\CompletedWithdraw;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Model\CompletedWithdraw
 *
 * @internal
 */
final class CompletedWithdrawTest extends TestCase
{
    /**
     * @covers ::construct
     * @covers ::getId
     * @covers ::getNetAmount
     * @covers ::getRecipientAddress
     */
    public function testGetters()
    {
        $id = 'id'.random_int(5, 10);
        $recipientAddress = 'ra'.random_int(5, 10);
        $netAmount = random_int(5, 10);

        $dto = new CompletedWithdraw($recipientAddress, $netAmount, $id);

        static::assertSame($id, $dto->getId());
        static::assertSame($recipientAddress, $dto->getRecipientAddress());
        static::assertSame($netAmount, $dto->getNetAmount());
    }
}

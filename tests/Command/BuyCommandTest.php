<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Command;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Command\BuyCommand
 *
 * @internal
 */
final class BuyCommandTest extends TestCase
{
    /**
     * @covers ::execute
     */
    public function testAmountIsNotNumeric(): void
    {
    }

    public function testNotUnattendedAndNotConfirming(): void
    {

    }

    public function testNotUnattendedAndConfirmsBuy(): void
    {

    }

    public function testUnattendedBuy(): void
    {

    }

    public function testOrderIsNotFilledImmediatelyButEventuallyFills(): void
    {

    }

    public function testOrderFailsToFillWithinTimeoutParameter(): void
    {

    }
}

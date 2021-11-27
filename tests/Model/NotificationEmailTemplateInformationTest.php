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

use Jorijn\Bitcoin\Dca\Model\NotificationEmailTemplateInformation;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Model\NotificationEmailTemplateInformation
 *
 * @internal
 */
final class NotificationEmailTemplateInformationTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getExchange
     * @covers ::getIconLocation
     * @covers ::getLogoLocation
     * @covers ::getQuotesLocation
     */
    public function testGetters(): void
    {
        $dto = new NotificationEmailTemplateInformation(
            $exchange = 'e'.random_int(1000, 2000),
            $logoLocation = 'l'.random_int(1000, 2000),
            $iconLocation = 'i'.random_int(1000, 2000),
            $quotesLocation = 'q'.random_int(1000, 2000)
        );

        static::assertSame($exchange, $dto->getExchange());
        static::assertSame($logoLocation, $dto->getLogoLocation());
        static::assertSame($iconLocation, $dto->getIconLocation());
        static::assertSame($quotesLocation, $dto->getQuotesLocation());
    }
}

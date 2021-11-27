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

use Jorijn\Bitcoin\Dca\Model\NotificationEmailConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Model\NotificationEmailConfiguration
 *
 * @internal
 */
final class NotificationEmailConfigurationTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getFrom
     * @covers ::getSubjectPrefix
     * @covers ::getTo
     */
    public function testGetters(): void
    {
        $dto = new NotificationEmailConfiguration(
            $to = 'to'.random_int(1000, 2000),
            $from = 'from'.random_int(1000, 2000),
            $subjectPrefix = 'sp'.random_int(1000, 2000)
        );

        static::assertSame($to, $dto->getTo());
        static::assertSame($from, $dto->getFrom());
        static::assertSame($subjectPrefix, $dto->getSubjectPrefix());
    }
}

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

use Jorijn\Bitcoin\Dca\Model\RemoteReleaseInformation;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Model\RemoteReleaseInformation
 *
 * @internal
 */
final class RemoteReleaseInformationTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getLocalVersion
     * @covers ::getReleaseInformation
     * @covers ::getRemoteVersion
     * @covers ::isOutdated
     */
    public function testGetters(): void
    {
        $releaseInformation = ['r' => random_int(1000, 2000)];

        $outdated = new RemoteReleaseInformation($releaseInformation, 'v1.0.0', 'v1.0.1');
        static::assertSame($releaseInformation, $outdated->getReleaseInformation());
        static::assertSame('v1.0.0', $outdated->getLocalVersion());
        static::assertSame('v1.0.1', $outdated->getRemoteVersion());
        static::assertTrue($outdated->isOutdated());

        $sameVersion = new RemoteReleaseInformation($releaseInformation, 'v1.0.0', 'v1.0.0');
        $newerVersion = new RemoteReleaseInformation($releaseInformation, 'v1.0.1', 'v1.0.0');

        static::assertFalse($sameVersion->isOutdated());
        static::assertFalse($newerVersion->isOutdated());
    }
}

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

namespace Tests\Jorijn\Bitcoin\Dca\Factory;

use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponentInterface;
use Jorijn\Bitcoin\Dca\Exception\NoDerivationComponentAvailableException;
use Jorijn\Bitcoin\Dca\Factory\DeriveFromMasterPublicKeyComponentFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Factory\DeriveFromMasterPublicKeyComponentFactory
 * @covers ::__construct
 *
 * @internal
 */
final class DeriveFromMasterPublicKeyComponentFactoryTest extends TestCase
{
    private const SUPPORTED = 'supported';

    /**
     * @covers ::createDerivationComponent
     */
    public function testOrderIsHandledCorrectly(): void
    {
        $service1 = $this->createMock(AddressFromMasterPublicKeyComponentInterface::class);
        $service1->expects(static::once())->method(self::SUPPORTED)->willReturn(true);
        $service2 = $this->createMock(AddressFromMasterPublicKeyComponentInterface::class);
        $service2->expects(static::never())->method(self::SUPPORTED);

        $deriveFromMasterPublicKeyComponentFactory = new DeriveFromMasterPublicKeyComponentFactory([
            $service1,
            $service2,
        ]);

        static::assertSame($service1, $deriveFromMasterPublicKeyComponentFactory->createDerivationComponent());
    }

    /**
     * @covers ::createDerivationComponent
     */
    public function testSupported(): void
    {
        $service1 = $this->createMock(AddressFromMasterPublicKeyComponentInterface::class);
        $service1->expects(static::once())->method(self::SUPPORTED)->willReturn(true);
        $service2 = $this->createMock(AddressFromMasterPublicKeyComponentInterface::class);
        $service2->expects(static::once())->method(self::SUPPORTED)->willReturn(false);

        $deriveFromMasterPublicKeyComponentFactory = new DeriveFromMasterPublicKeyComponentFactory([
            $service2,
            $service1,
        ]);

        static::assertSame($service1, $deriveFromMasterPublicKeyComponentFactory->createDerivationComponent());
    }

    /**
     * @covers ::createDerivationComponent
     */
    public function testSupportedIsEmpty(): void
    {
        $deriveFromMasterPublicKeyComponentFactory = new DeriveFromMasterPublicKeyComponentFactory([]);
        $this->expectException(NoDerivationComponentAvailableException::class);
        $deriveFromMasterPublicKeyComponentFactory->createDerivationComponent();
    }
}

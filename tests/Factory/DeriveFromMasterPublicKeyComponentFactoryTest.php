<?php

declare(strict_types=1);

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

        $factory = new DeriveFromMasterPublicKeyComponentFactory([
            $service1,
            $service2,
        ]);

        static::assertSame($service1, $factory->createDerivationComponent());
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

        $factory = new DeriveFromMasterPublicKeyComponentFactory([
            $service2,
            $service1,
        ]);

        static::assertSame($service1, $factory->createDerivationComponent());
    }

    /**
     * @covers ::createDerivationComponent
     */
    public function testSupportedIsEmpty(): void
    {
        $factory = new DeriveFromMasterPublicKeyComponentFactory([]);
        $this->expectException(NoDerivationComponentAvailableException::class);
        $factory->createDerivationComponent();
    }
}

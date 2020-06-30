<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Component;

use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponent;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponent
 *
 * @internal
 */
final class AddressFromMasterPublicKeyComponentTest extends TestCase
{
    use MasterPublicKeyScenarioTrait;

    protected function setUp(): void
    {
        parent::setUp();

        if (PHP_INT_SIZE !== 8) {
            static::markTestSkipped('unsupported on non 64 bits systems');
        }
    }

    /**
     * @dataProvider providerOfScenarios
     * @covers ::derive
     */
    public function testDerive(string $xpub, array $expectedAddressList): void
    {
        $component = new AddressFromMasterPublicKeyComponent();
        foreach ($expectedAddressList as $index => $expectedAddress) {
            static::assertSame(
                $expectedAddress,
                $component->derive($xpub, '0/'.$index)
            );
        }
    }

    /**
     * @covers ::derive
     */
    public function testDeriveWithEmptyXpubKey(): void
    {
        $component = new AddressFromMasterPublicKeyComponent();
        $this->expectException(\InvalidArgumentException::class);
        $component->derive('');
    }

    /**
     * @covers ::derive
     */
    public function testDeriveWithUnsupportedKey(): void
    {
        $component = new AddressFromMasterPublicKeyComponent();
        $this->expectException(\RuntimeException::class);
        $component->derive('(╯°□°）╯︵ ┻━┻');
    }

    /**
     * @covers ::supported
     */
    public function testSupported(): void
    {
        $component = new AddressFromMasterPublicKeyComponent();
        static::assertSame(PHP_INT_SIZE === 8, $component->supported());
    }
}

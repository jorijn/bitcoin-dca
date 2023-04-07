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

namespace Tests\Jorijn\Bitcoin\Dca\Component;

use Jorijn\Bitcoin\Dca\Component\ExternalAddressFromMasterPublicKeyComponent;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Component\ExternalAddressFromMasterPublicKeyComponent
 *
 * @covers ::__construct
 *
 * @internal
 */
final class ExternalAddressFromMasterPublicKeyComponentTest extends TestCase
{
    use MasterPublicKeyScenarioTrait;

    private const XPUB_PYTHON_CLI = 'XPUB_PYTHON_CLI';

    private \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;
    private ExternalAddressFromMasterPublicKeyComponent $component;

    protected function setUp(): void
    {
        parent::setUp();

        if (false === getenv(self::XPUB_PYTHON_CLI)) {
            static::markTestSkipped('setting XPUB_PYTHON_CLI is empty or does not exists');
        }

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->component = new ExternalAddressFromMasterPublicKeyComponent(
            $this->logger,
            getenv(self::XPUB_PYTHON_CLI)
        );
    }

    /**
     * @dataProvider providerOfScenarios
     *
     * @covers ::derive
     */
    public function testDerive(string $xpub, array $expectedAddressList): void
    {
        foreach ($expectedAddressList as $index => $expectedAddress) {
            static::assertSame(
                $expectedAddress,
                $this->component->derive($xpub, '0/'.$index)
            );
        }
    }

    /**
     * @covers ::derive
     */
    public function testDeriveWithEmptyXpubKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->component->derive('');
    }

    /**
     * @covers ::derive
     */
    public function testDeriveWithChangeAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->component->derive('dummy', '1/0');
    }

    /**
     * @covers ::derive
     */
    public function testDeriveWithUnsupportedKey(): void
    {
        $this->expectException(\Throwable::class);
        $this->component->derive('(╯°□°）╯︵ ┻━┻');
    }

    /**
     * @covers ::supported
     */
    public function testSupported(): void
    {
        static::assertTrue($this->component->supported());
    }
}

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

namespace Tests\Jorijn\Bitcoin\Dca\Provider;

use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponent;
use Jorijn\Bitcoin\Dca\Provider\XpubWithdrawAddressProvider;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Jorijn\Bitcoin\Dca\Validator\ValidationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Provider\XpubWithdrawAddressProvider
 * @covers ::__construct
 *
 * @internal
 */
final class XpubWithdrawAddressProviderTest extends TestCase
{
    /** @var MockObject|TaggedIntegerRepositoryInterface */
    private $xpubRepository;
    /** @var AddressFromMasterPublicKeyComponent|MockObject */
    private $keyFactory;
    /** @var MockObject|ValidationInterface */
    private $validation;
    private XpubWithdrawAddressProvider $provider;
    private string $configuredXPub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xpubRepository = $this->createMock(TaggedIntegerRepositoryInterface::class);
        $this->keyFactory = $this->createMock(AddressFromMasterPublicKeyComponent::class);
        $this->configuredXPub = 'xpub'.random_int(1000, 2000);
        $this->validation = $this->createMock(ValidationInterface::class);
        $this->provider = new XpubWithdrawAddressProvider(
            $this->validation,
            $this->keyFactory,
            $this->xpubRepository,
            $this->configuredXPub,
        );
    }

    /**
     * @covers ::provide
     */
    public function testProvideResultsInDerivedAddress(): void
    {
        $activeIndex = random_int(1000, 2000);
        $generatedAddress = 'address'.random_int(1000, 2000);

        $this->xpubRepository
            ->expects(static::atLeastOnce())
            ->method('get')
            ->with($this->configuredXPub)
            ->willReturn($activeIndex)
        ;

        $this->keyFactory
            ->expects(static::atLeastOnce())
            ->method('derive')
            ->with($this->configuredXPub, '0/'.$activeIndex)
            ->willReturn($generatedAddress)
        ;

        $this->validation
            ->expects(static::once())
            ->method('validate')
            ->with($generatedAddress)
        ;

        static::assertSame($generatedAddress, $this->provider->provide());
    }
}

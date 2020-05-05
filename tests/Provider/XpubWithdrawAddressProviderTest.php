<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Provider;

use Jorijn\Bl3pDca\Factory\AddressFromMasterPublicKeyFactory;
use Jorijn\Bl3pDca\Provider\XpubWithdrawAddressProvider;
use Jorijn\Bl3pDca\Repository\TaggedIntegerRepositoryInterface;
use Jorijn\Bl3pDca\Validator\ValidationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Provider\XpubWithdrawAddressProvider
 * @covers ::__construct
 *
 * @internal
 */
final class XpubWithdrawAddressProviderTest extends TestCase
{
    /** @var MockObject|TaggedIntegerRepositoryInterface */
    private $xpubRepository;
    /** @var AddressFromMasterPublicKeyFactory|MockObject */
    private $keyFactory;
    /** @var MockObject|ValidationInterface */
    private $validation;
    private XpubWithdrawAddressProvider $provider;
    private string $configuredXPub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xpubRepository = $this->createMock(TaggedIntegerRepositoryInterface::class);
        $this->keyFactory = $this->createMock(AddressFromMasterPublicKeyFactory::class);
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

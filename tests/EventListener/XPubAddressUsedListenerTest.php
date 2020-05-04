<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\EventListener;

use Jorijn\Bl3pDca\Event\WithdrawSuccessEvent;
use Jorijn\Bl3pDca\EventListener\XPubAddressUsedListener;
use Jorijn\Bl3pDca\Factory\AddressFromMasterPublicKeyFactory;
use Jorijn\Bl3pDca\Repository\TaggedIntegerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\EventListener\XPubAddressUsedListener
 * @covers ::__construct
 *
 * @internal
 */
final class XPubAddressUsedListenerTest extends TestCase
{
    /** @var MockObject|TaggedIntegerRepositoryInterface */
    private $xpubRepository;
    /** @var AddressFromMasterPublicKeyFactory|MockObject */
    private $keyFactory;
    /** @var LoggerInterface|MockObject */
    private $logger;
    private XPubAddressUsedListener $listener;
    private WithdrawSuccessEvent $event;
    private string $configuredXPub;
    private string $addressUsed;
    private int $amount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xpubRepository = $this->createMock(TaggedIntegerRepositoryInterface::class);
        $this->keyFactory = $this->createMock(AddressFromMasterPublicKeyFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configuredXPub = 'xpub'.random_int(1000, 2000);
        $this->listener = new XPubAddressUsedListener(
            $this->xpubRepository,
            $this->keyFactory,
            $this->logger,
            $this->configuredXPub
        );

        $this->addressUsed = 'address'.random_int(1000, 2000);
        $this->amount = random_int(1000, 2000);
        $this->event = new WithdrawSuccessEvent($this->addressUsed, $this->amount);
    }

    /**
     * @covers ::onWithdrawAddressUsed
     */
    public function testXpubCannotBeEmpty(): void
    {
        $this->listener = new XPubAddressUsedListener(
            $this->xpubRepository,
            $this->keyFactory,
            $this->logger,
            null
        );

        $this->xpubRepository
            ->expects(static::never())
            ->method('increase')
        ;

        $this->listener->onWithdrawAddressUsed($this->event);
    }

    /**
     * @covers ::onWithdrawAddressUsed
     */
    public function testDerivedAddressDoesNotMatchesWithdrawalAddress(): void
    {
        $activeIndex = random_int(1000, 2000);
        $otherAddress = 'da'.random_int(1000, 2000);

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
            ->willReturn($otherAddress)
        ;

        $this->xpubRepository
            ->expects(static::never())
            ->method('increase')
        ;

        $this->listener->onWithdrawAddressUsed($this->event);
    }

    /**
     * @covers ::onWithdrawAddressUsed
     */
    public function testXpubIndexIsIncreasedOnWithdraw(): void
    {
        $activeIndex = random_int(1000, 2000);

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
            ->willReturn($this->addressUsed)
        ;

        $this->xpubRepository
            ->expects(static::once())
            ->method('increase')
            ->with($this->configuredXPub)
        ;

        $this->listener->onWithdrawAddressUsed($this->event);
    }

    /**
     * @covers ::onWithdrawAddressUsed
     */
    public function testFailureIsLogged(): void
    {
        $activeIndex = random_int(1000, 2000);
        $exception = new \Exception('error'.random_int(1000, 2000));

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
            ->willReturn($this->addressUsed)
        ;

        $this->xpubRepository
            ->expects(static::once())
            ->method('increase')
            ->with($this->configuredXPub)
            ->willThrowException($exception)
        ;

        $this->logger
            ->expects(static::once())
            ->method('error')
        ;

        $this->expectExceptionObject($exception);

        $this->listener->onWithdrawAddressUsed($this->event);
    }
}

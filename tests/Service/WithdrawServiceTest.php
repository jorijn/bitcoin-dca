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

namespace Tests\Jorijn\Bitcoin\Dca\Service;

use Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent;
use Jorijn\Bitcoin\Dca\Exception\NoExchangeAvailableException;
use Jorijn\Bitcoin\Dca\Exception\NoRecipientAddressAvailableException;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Provider\WithdrawAddressProviderInterface;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Jorijn\Bitcoin\Dca\Service\WithdrawService;
use Jorijn\Bitcoin\Dca\Service\WithdrawServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\WithdrawService
 * @covers ::__construct
 *
 * @internal
 */
final class WithdrawServiceTest extends TestCase
{
    private const ADDRESS = 'address';

    /** @var MockObject|WithdrawAddressProviderInterface */
    private $addressProvider;
    /** @var MockObject|WithdrawServiceInterface */
    private $supportedService;
    /** @var EventDispatcherInterface|MockObject */
    private $dispatcher;
    /** @var LoggerInterface|MockObject */
    private $logger;
    private string $configuredExchange;
    /** @var MockObject|TaggedIntegerRepositoryInterface */
    private $balanceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addressProvider = $this->createMock(WithdrawAddressProviderInterface::class);
        $this->supportedService = $this->createMock(WithdrawServiceInterface::class);
        $this->balanceRepository = $this->createMock(TaggedIntegerRepositoryInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configuredExchange = 'ce'.random_int(1000, 2000);
    }

    public function providerOfTags(): array
    {
        return [
            'with tag' => ['tag'.random_int(1000, 2000)],
            'without tag' => [null],
        ];
    }

    public function providerOfBalancesAndTags(): array
    {
        return [
            'tag, exchange is limiting factor' => [1000, 'tag'.random_int(1000, 2000), 2000, 1000],
            'tag, tag is limiting factor' => [3000, 'tag'.random_int(1000, 2000), 2000, 2000],
            'no tag' => [1000, null, null, 1000],
        ];
    }

    /**
     * @covers ::getActiveService
     * @covers ::getWithdrawFeeInSatoshis
     */
    public function testGettingWithdrawFee(): void
    {
        $this->expectSupportedCheckToService();
        $fee = random_int(1000, 2000);

        $this->supportedService
            ->expects(static::once())
            ->method('getWithdrawFeeInSatoshis')
            ->willReturn($fee)
        ;

        $returnedFee = (new WithdrawService(
            [$this->addressProvider],
            [$this->supportedService],
            $this->balanceRepository,
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange
        ))->getWithdrawFeeInSatoshis();

        static::assertSame($fee, $returnedFee);
    }

    /**
     * @covers ::getActiveService
     * @covers ::withdraw
     * @dataProvider providerOfTags
     *
     * @throws \Throwable
     */
    public function testWithdrawHappyFlow(?string $tag): void
    {
        $this->expectSupportedCheckToService();

        $balance = random_int(1000, 2000);
        $address = self::ADDRESS.random_int(1000, 2000);
        $id = 'id'.random_int(1000, 2000);

        $withdrawDTO = new CompletedWithdraw($address, $balance, $id);

        $this->logger
            ->expects(static::atLeastOnce())
            ->method('info')
        ;

        $this->supportedService
            ->expects(static::once())
            ->method('withdraw')
            ->with($balance, $address)
            ->willReturn($withdrawDTO)
        ;

        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function (WithdrawSuccessEvent $event) use ($tag, $withdrawDTO) {
                self::assertSame($withdrawDTO, $event->getCompletedWithdraw());
                self::assertSame($tag, $event->getTag());

                return true;
            }))
        ;

        $completedWithdraw = (new WithdrawService(
            [$this->addressProvider],
            [$this->supportedService],
            $this->balanceRepository,
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange
        ))->withdraw($balance, $address, $tag);

        static::assertSame($withdrawDTO, $completedWithdraw);
    }

    /**
     * @covers ::getActiveService
     * @covers ::withdraw
     * @dataProvider providerOfTags
     */
    public function testWithdrawFails(?string $tag): void
    {
        $this->expectSupportedCheckToService();

        $balance = random_int(1000, 2000);
        $address = self::ADDRESS.random_int(1000, 2000);

        $this->logger
            ->expects(static::atLeastOnce())
            ->method('error')
        ;

        $error = new \RuntimeException('random'.random_int(1000, 2000));
        $this->supportedService
            ->expects(static::once())
            ->method('withdraw')
            ->with($balance, $address)
            ->willThrowException($error)
        ;

        $this->dispatcher
            ->expects(static::never())
            ->method('dispatch')
        ;

        $this->expectExceptionObject($error);

        (new WithdrawService(
            [$this->addressProvider],
            [$this->supportedService],
            $this->balanceRepository,
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange
        ))->withdraw($balance, $address, $tag);
    }

    /**
     * @covers ::getActiveService
     * @covers ::getBalance
     * @dataProvider providerOfBalancesAndTags
     */
    public function testGetBalanceForActiveExchange(
        int $exchangeBalance,
        ?string $tag,
        ?int $taggedBalance,
        int $expectedBalance
    ): void {
        $this->expectSupportedCheckToService();

        $this->supportedService
            ->expects(static::once())
            ->method('getAvailableBalance')
            ->willReturn($exchangeBalance)
        ;

        if ($tag) {
            $this->balanceRepository
                ->expects(static::once())
                ->method('get')
                ->with($tag)
                ->willReturn($taggedBalance)
            ;
        }

        $returnedBalance = (new WithdrawService(
            [$this->addressProvider],
            [$this->supportedService],
            $this->balanceRepository,
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange
        ))->getBalance($tag);

        static::assertSame($expectedBalance, $returnedBalance);
    }

    /**
     * @covers ::getRecipientAddress
     *
     * @throws \Exception
     */
    public function testGetRecipientAddress(): void
    {
        $address = self::ADDRESS.random_int(1000, 2000);

        $unsupportedAddressProvider = $this->createMock(WithdrawAddressProviderInterface::class);
        $unsupportedAddressProvider
            ->expects(static::exactly(2))
            ->method('provide')
            ->willThrowException(new \RuntimeException('test failure'))
        ;

        $this->addressProvider
            ->expects(static::once())
            ->method('provide')
            ->willReturn($address)
        ;

        $returnedAddress = (new WithdrawService(
            [$unsupportedAddressProvider, $this->addressProvider],
            [$this->supportedService],
            $this->balanceRepository,
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange
        ))->getRecipientAddress();

        static::assertSame($address, $returnedAddress);

        // test handling of no capable providers
        $this->expectException(NoRecipientAddressAvailableException::class);
        (new WithdrawService(
            [$unsupportedAddressProvider],
            [$this->supportedService],
            $this->balanceRepository,
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange
        ))->getRecipientAddress();
    }

    /**
     * @covers ::getActiveService
     * @covers ::getBalance
     */
    public function testNoExchangeAvailable(): void
    {
        $unsupportedService = $this->createMock(WithdrawServiceInterface::class);
        $unsupportedService
            ->expects(static::once())
            ->method('supportsExchange')
            ->with($this->configuredExchange)
            ->willReturn(false)
        ;

        $this->expectException(NoExchangeAvailableException::class);

        (new WithdrawService(
            [$this->addressProvider],
            [$unsupportedService],
            $this->balanceRepository,
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange
        ))->getBalance();
    }

    protected function expectSupportedCheckToService(): void
    {
        $this->supportedService
            ->expects(static::once())
            ->method('supportsExchange')
            ->with($this->configuredExchange)
            ->willReturn(true)
        ;
    }
}

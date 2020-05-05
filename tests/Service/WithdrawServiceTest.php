<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Service;

use Jorijn\Bl3pDca\Client\Bl3pClientInterface;
use Jorijn\Bl3pDca\Event\WithdrawSuccessEvent;
use Jorijn\Bl3pDca\Exception\NoRecipientAddressAvailableException;
use Jorijn\Bl3pDca\Provider\WithdrawAddressProviderInterface;
use Jorijn\Bl3pDca\Repository\TaggedIntegerRepositoryInterface;
use Jorijn\Bl3pDca\Service\WithdrawService;
use Jorijn\Bl3pDca\Validator\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Service\WithdrawService
 * @covers ::__construct
 *
 * @internal
 */
final class WithdrawServiceTest extends TestCase
{
    /** @var Bl3pClientInterface|MockObject */
    private $client;
    /** @var MockObject|TaggedIntegerRepositoryInterface */
    private $balanceRepository;
    /** @var EventDispatcherInterface|MockObject */
    private $dispatcher;
    /** @var LoggerInterface|MockObject */
    private $logger;
    private WithdrawService $service;
    private array $addressProviders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Bl3pClientInterface::class);
        $this->addressProviders = [];
        $this->balanceRepository = $this->createMock(TaggedIntegerRepositoryInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new WithdrawService(
            $this->client,
            $this->addressProviders,
            $this->balanceRepository,
            $this->dispatcher,
            $this->logger
        );
    }

    public function providerOfTags(): array
    {
        return ['tag' => ['tag'.random_int(1000, 2000)], 'without tag' => [null]];
    }

    /**
     * @covers ::getRecipientAddress
     *
     * @throws \Exception
     */
    public function testGetRecipientAddressFromActiveAddressProvider(): void
    {
        $recipientAddress = 'address'.random_int(1000, 2000);
        $failingProvider = $this->createMock(WithdrawAddressProviderInterface::class);
        $workingProvider = $this->createMock(WithdrawAddressProviderInterface::class);

        $failingProvider->method('provide')->willThrowException(new ValidationException('error'));
        $workingProvider->method('provide')->willReturn($recipientAddress);

        $this->service = new WithdrawService(
            $this->client,
            [
                $failingProvider,
                $workingProvider,
            ],
            $this->balanceRepository,
            $this->dispatcher,
            $this->logger
        );

        static::assertSame($recipientAddress, $this->service->getRecipientAddress());
    }

    /**
     * @covers ::getRecipientAddress
     */
    public function testNoAddressProviderAvailable(): void
    {
        $this->expectException(NoRecipientAddressAvailableException::class);

        $this->service->getRecipientAddress();
    }

    /**
     * @covers ::getBalance
     *
     * @throws \Exception
     */
    public function testGetBalanceWithoutTag(): void
    {
        $balance = random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('GENMKT/money/info')
            ->willReturn($this->createBalanceStructure($balance))
        ;

        $this->balanceRepository
            ->expects(static::never())
            ->method('get')
        ;

        static::assertSame($balance, $this->service->getBalance(true));
    }

    /**
     * @covers ::getBalance
     *
     * @throws \Exception
     */
    public function testGetBalanceForTagButLessBalanceIsAvailable(): void
    {
        $balanceAvailable = random_int(1000, 2000);
        $taggedBalance = random_int(3000, 5000);
        $tag = 'tag'.random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('GENMKT/money/info')
            ->willReturn($this->createBalanceStructure($balanceAvailable))
        ;

        $this->balanceRepository
            ->expects(static::once())
            ->method('get')
            ->with($tag)
            ->willReturn($taggedBalance)
        ;

        static::assertSame($balanceAvailable, $this->service->getBalance(true, $tag));
    }

    /**
     * @covers ::getBalance
     */
    public function testGetSpecificAmountFromBalance(): void
    {
        // this is not implemented, but it might in the future
        static::assertSame(0, $this->service->getBalance(false));
    }

    /**
     * @covers ::getBalance
     *
     * @throws \Exception
     */
    public function testGetBalanceForTag(): void
    {
        $balanceAvailable = random_int(7000, 9000);
        $taggedBalance = random_int(3000, 5000);
        $tag = 'tag'.random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('GENMKT/money/info')
            ->willReturn($this->createBalanceStructure($balanceAvailable))
        ;

        $this->balanceRepository
            ->expects(static::once())
            ->method('get')
            ->with($tag)
            ->willReturn($taggedBalance)
        ;

        static::assertSame($taggedBalance, $this->service->getBalance(true, $tag));
    }

    /**
     * @dataProvider providerOfTags
     * @covers ::withdraw
     *
     * @throws \Exception
     */
    public function testWithdraw(string $tag = null): void
    {
        $address = 'address'.random_int(1000, 2000);
        $amount = random_int(100000, 300000);
        $netAmount = $amount - WithdrawService::WITHDRAW_FEE;
        $withdrawID = 'id'.random_int(1000, 2000);
        $apiResponse = ['data' => ['id' => $withdrawID]];

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with(
                'GENMKT/money/withdraw',
                static::callback(function (array $parameters) use ($netAmount, $amount, $address) {
                    self::assertArrayHasKey('currency', $parameters);
                    self::assertSame('BTC', $parameters['currency']);
                    self::assertArrayHasKey('address', $parameters);
                    self::assertSame($address, $parameters['address']);
                    self::assertArrayHasKey('amount_int', $parameters);
                    self::assertSame($netAmount, $parameters['amount_int']);

                    return true;
                })
            )
            ->willReturn($apiResponse)
        ;

        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(function (WithdrawSuccessEvent $event) use ($address, $netAmount, $withdrawID, $tag) {
                self::assertSame($tag, $event->getTag());
                self::assertSame($withdrawID, $event->getCompletedWithdraw()->getId());
                self::assertSame($netAmount, $event->getCompletedWithdraw()->getNetAmount());
                self::assertSame($address, $event->getCompletedWithdraw()->getRecipientAddress());

                return true;
            }))
        ;

        $this->logger->expects(static::atLeastOnce())->method('info');

        $dto = $this->service->withdraw($amount, $address, $tag);
        static::assertSame($withdrawID, $dto->getId());
        static::assertSame($netAmount, $dto->getNetAmount());
        static::assertSame($address, $dto->getRecipientAddress());
    }

    private function createBalanceStructure(int $balance): array
    {
        return [
            'data' => [
                'wallets' => [
                    'BTC' => [
                        'available' => [
                            'value_int' => $balance,
                        ],
                    ],
                ],
            ],
        ];
    }
}

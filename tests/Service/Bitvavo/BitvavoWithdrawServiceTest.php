<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Bitvavo;

use Jorijn\Bitcoin\Dca\Client\BitvavoClientInterface;
use Jorijn\Bitcoin\Dca\Service\Bitvavo\BitvavoWithdrawService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Bitvavo\BitvavoWithdrawService
 * @covers ::__construct
 *
 * @internal
 */
final class BitvavoWithdrawServiceTest extends TestCase
{
    public const ADDRESS = 'address';
    public const API_CALL = 'apiCall';
    public const GENMKT_MONEY_INFO = 'GENMKT/money/info';

    /** @var BitvavoClientInterface|MockObject */
    private $client;
    /** @var LoggerInterface|MockObject */
    private $logger;
    private BitvavoWithdrawService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(BitvavoClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new BitvavoWithdrawService(
            $this->client,
            $this->logger
        );
    }

    /**
     * @covers ::getAvailableBalance
     *
     * @throws \Exception
     */
    public function testGetBalance(): void
    {
        $this->client
            ->expects(static::exactly(2))
            ->method('apiCall')
            ->with('balance', 'GET', [BitvavoWithdrawService::SYMBOL => 'BTC'])
            ->willReturnOnConsecutiveCalls(
                [['symbol' => 'BTC', 'available' => 2.345, 'inOrder' => 1]],
                []
            )
        ;

        static::assertSame(134500000, $this->service->getAvailableBalance());
        static::assertSame(0, $this->service->getAvailableBalance());
    }

    /**
     * @covers ::withdraw
     *
     * @throws \Exception
     */
    public function testWithdraw(): void
    {
        $address = self::ADDRESS.random_int(1000, 2000);
        $amount = random_int(100000, 300000);
        $apiResponse = [];

        $bitvavoFee = random_int(1000, 2000);
        $netAmount = $amount - $bitvavoFee;
        $this->client
            ->expects(static::exactly(2))
            ->method(self::API_CALL)
            ->withConsecutive(
                ['assets', 'GET', [BitvavoWithdrawService::SYMBOL => 'BTC']],
                [
                    'withdrawal',
                    'POST',
                    [],
                    static::callback(static function (array $parameters) use ($netAmount, $address) {
                        self::assertArrayHasKey('symbol', $parameters);
                        self::assertSame('BTC', $parameters['symbol']);
                        self::assertArrayHasKey(self::ADDRESS, $parameters);
                        self::assertSame($address, $parameters[self::ADDRESS]);
                        self::assertArrayHasKey('amount', $parameters);
                        self::assertSame((string) ($netAmount / 100000000), $parameters['amount']);
                        self::assertArrayHasKey('addWithdrawalFee', $parameters);
                        self::assertTrue($parameters['addWithdrawalFee']);

                        return true;
                    }),
                ]
            )
            ->willReturnOnConsecutiveCalls(
                ['withdrawalFee' => $bitvavoFee / 100000000],
                $apiResponse
            )
        ;

        $dto = $this->service->withdraw($amount, $address);

        static::assertSame($netAmount, $dto->getNetAmount());
        static::assertSame($address, $dto->getRecipientAddress());
    }

    /**
     * @covers ::supportsExchange
     */
    public function testSupportsExchange(): void
    {
        static::assertTrue($this->service->supportsExchange('bitvavo'));
        static::assertFalse($this->service->supportsExchange('bitvivo'));
    }

    /**
     * @covers ::getWithdrawFeeInSatoshis
     */
    public function testFeeCalculation(): void
    {
        $bitvavoFee = random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('assets', 'GET', [BitvavoWithdrawService::SYMBOL => 'BTC'])
            ->willReturn(['withdrawalFee' => $bitvavoFee / 100000000])
        ;

        $providedFee = $this->service->getWithdrawFeeInSatoshis();

        static::assertEqualsWithDelta($bitvavoFee, $providedFee, 1.0);
    }
}

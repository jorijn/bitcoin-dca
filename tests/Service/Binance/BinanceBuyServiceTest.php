<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Binance;

use Jorijn\Bitcoin\Dca\Client\BinanceClientInterface;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Service\Binance\BinanceBuyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Binance\BinanceBuyService
 * @covers ::__construct
 *
 * @internal
 */
final class BinanceBuyServiceTest extends TestCase
{
    /** @var BinanceClientInterface|MockObject */
    protected $client;
    protected string $baseCurrency;
    protected string $tradingPair;
    protected BinanceBuyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(BinanceClientInterface::class);
        $this->baseCurrency = 'BC'.random_int(1, 9);
        $this->tradingPair = 'BTC'.$this->baseCurrency;

        $this->service = new BinanceBuyService($this->client, $this->baseCurrency);
    }

    /**
     * @covers ::supportsExchange
     */
    public function testSupportsExchange(): void
    {
        static::assertTrue($this->service->supportsExchange('binance'));
        static::assertFalse($this->service->supportsExchange('kraken'));
    }

    /**
     * @covers ::getAveragePrice
     * @covers ::getCompletedBuyOrderFromResponse
     * @covers ::getFeeInformationFromOrderInfo
     * @covers ::initiateBuy
     */
    public function testBuySucceedsFirstTime(): void
    {
        $amount = random_int(100, 200);

        $apiResponse = [
            'executedQty' => '0.005',
            'cummulativeQuoteQty' => $amount,
            'transactTime' => 123,
            'status' => 'FILLED',
            'fills' => [
                ['commission' => '1.0', 'commissionAsset' => 'BNB', 'qty' => '0.002', 'price' => '1000.25'],
                ['commission' => '1.0', 'commissionAsset' => 'BNB', 'qty' => '0.002', 'price' => '2000.75'],
            ],
        ];

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'POST',
                BinanceBuyService::ORDER_URL,
                static::callback(function (array $options) use ($amount) {
                    self::assertArrayHasKey('extra', $options);
                    self::assertArrayHasKey('body', $options);
                    self::assertSame(['security_type' => 'TRADE'], $options['extra']);
                    self::assertSame($this->tradingPair, $options['body']['symbol']);
                    self::assertArrayHasKey('quoteOrderQty', $options['body']);
                    self::assertSame($amount, $options['body']['quoteOrderQty']);
                    self::assertArrayHasKey('symbol', $options['body']);
                    self::assertArrayHasKey('side', $options['body']);
                    self::assertSame('MARKET', $options['body']['type']);
                    self::assertArrayHasKey('newOrderRespType', $options['body']);
                    self::assertSame('FULL', $options['body']['newOrderRespType']);
                    self::assertSame('BUY', $options['body']['side']);
                    self::assertArrayHasKey('type', $options['body']);

                    return true;
                })
            )
            ->willReturn($apiResponse)
        ;

        $result = $this->service->initiateBuy($amount);

        static::assertSame(500000, $result->getAmountInSatoshis());
        static::assertSame(0, $result->getFeesInSatoshis());
        static::assertSame('0.005 BTC', $result->getDisplayAmountBought());
        static::assertSame($amount.' '.$this->baseCurrency, $result->getDisplayAmountSpent());
        static::assertSame('1500.5 '.$this->baseCurrency, $result->getDisplayAveragePrice());
        static::assertSame('2.0 BNB', $result->getDisplayFeesSpent());
    }

    /**
     * @covers ::getAveragePrice
     * @covers ::getCompletedBuyOrderFromResponse
     * @covers ::getFeeInformationFromOrderInfo
     * @covers ::initiateBuy
     */
    public function testBuyWithFeesInBitcoin(): void
    {
        $amount = random_int(100, 200);

        $apiResponse = [
            'executedQty' => '0.006',
            'cummulativeQuoteQty' => $amount,
            'transactTime' => 123,
            'status' => 'FILLED',
            'fills' => [
                ['commission' => '0.0002', 'commissionAsset' => 'BTC', 'qty' => '0.004', 'price' => '5000.25'],
                ['commission' => '0.0001', 'commissionAsset' => 'BTC', 'qty' => '0.004', 'price' => '6000.75'],
            ],
        ];

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'POST',
                BinanceBuyService::ORDER_URL,
            )
            ->willReturn($apiResponse)
        ;

        $result = $this->service->initiateBuy($amount);

        static::assertSame(30000, $result->getFeesInSatoshis());
        static::assertSame('5500.5 '.$this->baseCurrency, $result->getDisplayAveragePrice());
        static::assertSame('0.0003 BTC', $result->getDisplayFeesSpent());
        static::assertSame($amount.' '.$this->baseCurrency, $result->getDisplayAmountSpent());
        static::assertSame('0.006 BTC', $result->getDisplayAmountBought());
        static::assertSame(600000, $result->getAmountInSatoshis());
    }

    /**
     * @covers ::getAveragePrice
     * @covers ::getCompletedBuyOrderFromResponse
     * @covers ::getFeeInformationFromOrderInfo
     * @covers ::initiateBuy
     */
    public function testBuyButNotFilledYet(): void
    {
        $orderId = (string) random_int(100, 200);

        $apiResponse = [
            'transactTime' => 123,
            'orderId' => $orderId,
            'status' => 'PARTIAL',
        ];

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'POST',
                BinanceBuyService::ORDER_URL,
            )
            ->willReturn($apiResponse)
        ;

        $this->expectException(PendingBuyOrderException::class);
        $this->service->initiateBuy(10);

        /** @var PendingBuyOrderException $exception */
        $exception = $this->getExpectedException();
        static::assertSame($orderId, $exception->getOrderId());
    }

    /**
     * @covers ::checkIfOrderIsFilled
     * @covers ::getAveragePrice
     * @covers ::getCompletedBuyOrderFromResponse
     * @covers ::getFeeInformationFromOrderInfo
     */
    public function testBuyFulfillsAfterCheck(): void
    {
        $amount = random_int(100, 200);
        $orderId = (string) random_int(100, 200);
        $time = time();

        $getResponse = [
            'executedQty' => '0.006',
            'cummulativeQuoteQty' => $amount,
            'transactTime' => 123,
            'orderId' => $orderId,
            'status' => 'FILLED',
            'time' => $time,
        ];

        $infoResponse = [
            ['commission' => '0.0002', 'commissionAsset' => 'BTC', 'qty' => '0.003', 'price' => '2000.25'],
            ['commission' => '0.0001', 'commissionAsset' => 'BTC', 'qty' => '0.003', 'price' => '3000.75'],
        ];

        $this->client
            ->expects(static::exactly(2))
            ->method('request')
            ->withConsecutive(
                [
                    'GET',
                    BinanceBuyService::ORDER_URL,
                    static::callback(function (array $options) use ($orderId) {
                        self::assertArrayHasKey('symbol', $options['body']);
                        self::assertSame(['security_type' => 'TRADE'], $options['extra']);
                        self::assertArrayHasKey('orderId', $options['body']);
                        self::assertSame($this->tradingPair, $options['body']['symbol']);
                        self::assertSame($orderId, $options['body']['orderId']);
                        self::assertArrayHasKey('extra', $options);
                        self::assertArrayHasKey('body', $options);

                        return true;
                    }),
                ],
                [
                    'GET',
                    'api/v3/myTrades',
                    static::callback(function (array $options) use ($time) {
                        self::assertArrayHasKey('extra', $options);
                        self::assertArrayHasKey('symbol', $options['body']);
                        self::assertSame($this->tradingPair, $options['body']['symbol']);
                        self::assertArrayHasKey('startTime', $options['body']);
                        self::assertArrayHasKey('body', $options);
                        self::assertSame($time, $options['body']['startTime']);
                        self::assertSame(['security_type' => 'USER_DATA'], $options['extra']);

                        return true;
                    }),
                ],
            )
            ->willReturnOnConsecutiveCalls($getResponse, $infoResponse)
        ;

        $result = $this->service->checkIfOrderIsFilled($orderId);

        static::assertSame(600000, $result->getAmountInSatoshis());
        static::assertSame(30000, $result->getFeesInSatoshis());
        static::assertSame('0.006 BTC', $result->getDisplayAmountBought());
        static::assertSame($amount.' '.$this->baseCurrency, $result->getDisplayAmountSpent());
        static::assertSame('2500.5 '.$this->baseCurrency, $result->getDisplayAveragePrice());
        static::assertSame('0.0003 BTC', $result->getDisplayFeesSpent());
    }

    /**
     * @covers ::checkIfOrderIsFilled
     * @covers ::getAveragePrice
     * @covers ::getCompletedBuyOrderFromResponse
     * @covers ::getFeeInformationFromOrderInfo
     */
    public function testBuyIsStillNotFilled(): void
    {
        $amount = random_int(100, 200);
        $orderId = (string) random_int(100, 200);
        $time = time();

        $getResponse = [
            'executedQty' => '0.005',
            'cummulativeQuoteQty' => $amount,
            'transactTime' => 123,
            'orderId' => $orderId,
            'status' => 'PARTIAL',
            'time' => $time,
        ];

        $this->client
            ->expects(static::exactly(1))
            ->method('request')
            ->with(
                'GET',
                BinanceBuyService::ORDER_URL,
                static::callback(function (array $options) use ($orderId) {
                    self::assertArrayHasKey('extra', $options);
                    self::assertArrayHasKey('body', $options);
                    self::assertSame(['security_type' => 'TRADE'], $options['extra']);
                    self::assertArrayHasKey('symbol', $options['body']);
                    self::assertSame($this->tradingPair, $options['body']['symbol']);
                    self::assertArrayHasKey('orderId', $options['body']);
                    self::assertSame($orderId, $options['body']['orderId']);

                    return true;
                })
            )
            ->willReturn($getResponse)
        ;

        $this->expectException(PendingBuyOrderException::class);

        $this->service->checkIfOrderIsFilled($orderId);

        /** @var PendingBuyOrderException $exception */
        $exception = $this->getExpectedException();
        static::assertSame($orderId, $exception->getOrderId());
    }

    /**
     * @covers ::cancelBuyOrder
     */
    public function testOrderCancellation(): void
    {
        $orderId = 'oid'.random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'DELETE',
                BinanceBuyService::ORDER_URL,
                static::callback(function (array $options) use ($orderId) {
                    self::assertArrayHasKey('extra', $options);
                    self::assertArrayHasKey('body', $options);
                    self::assertSame(['security_type' => 'TRADE'], $options['extra']);

                    self::assertArrayHasKey('symbol', $options['body']);
                    self::assertSame($this->tradingPair, $options['body']['symbol']);

                    self::assertArrayHasKey('orderId', $options['body']);
                    self::assertSame($orderId, $options['body']['orderId']);

                    return true;
                })
            )
        ;

        $this->service->cancelBuyOrder($orderId);
    }
}

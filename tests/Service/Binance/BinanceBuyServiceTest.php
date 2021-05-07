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
            'executedQty' => '0.004',
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
                'api/v3/order',
                static::callback(function (array $options) use ($amount) {
                    self::assertArrayHasKey('extra', $options);
                    self::assertArrayHasKey('body', $options);
                    self::assertSame(['security_type' => 'TRADE'], $options['extra']);

                    self::assertArrayHasKey('symbol', $options['body']);
                    self::assertSame($this->tradingPair, $options['body']['symbol']);
                    self::assertArrayHasKey('quoteOrderQty', $options['body']);
                    self::assertSame($amount, $options['body']['quoteOrderQty']);
                    self::assertArrayHasKey('side', $options['body']);
                    self::assertSame('BUY', $options['body']['side']);
                    self::assertArrayHasKey('type', $options['body']);
                    self::assertSame('MARKET', $options['body']['type']);
                    self::assertArrayHasKey('newOrderRespType', $options['body']);
                    self::assertSame('FULL', $options['body']['newOrderRespType']);

                    return true;
                })
            )
            ->willReturn($apiResponse)
        ;

        $result = $this->service->initiateBuy($amount);

        static::assertSame(400000, $result->getAmountInSatoshis());
        static::assertSame(0, $result->getFeesInSatoshis());
        static::assertSame('0.004 BTC', $result->getDisplayAmountBought());
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
            'executedQty' => '0.004',
            'cummulativeQuoteQty' => $amount,
            'transactTime' => 123,
            'status' => 'FILLED',
            'fills' => [
                ['commission' => '0.0002', 'commissionAsset' => 'BTC', 'qty' => '0.002', 'price' => '1000.25'],
                ['commission' => '0.0001', 'commissionAsset' => 'BTC', 'qty' => '0.002', 'price' => '2000.75'],
            ],
        ];

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'POST',
                'api/v3/order',
            )
            ->willReturn($apiResponse)
        ;

        $result = $this->service->initiateBuy($amount);

        static::assertSame(400000, $result->getAmountInSatoshis());
        static::assertSame(30000, $result->getFeesInSatoshis());
        static::assertSame('0.004 BTC', $result->getDisplayAmountBought());
        static::assertSame($amount.' '.$this->baseCurrency, $result->getDisplayAmountSpent());
        static::assertSame('1500.5 '.$this->baseCurrency, $result->getDisplayAveragePrice());
        static::assertSame('0.0003 BTC', $result->getDisplayFeesSpent());
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
                'api/v3/order',
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
            'executedQty' => '0.004',
            'cummulativeQuoteQty' => $amount,
            'transactTime' => 123,
            'orderId' => $orderId,
            'status' => 'FILLED',
            'time' => $time,
        ];

        $infoResponse = [
            ['commission' => '0.0002', 'commissionAsset' => 'BTC', 'qty' => '0.002', 'price' => '1000.25'],
            ['commission' => '0.0001', 'commissionAsset' => 'BTC', 'qty' => '0.002', 'price' => '2000.75'],
        ];

        $this->client
            ->expects(static::exactly(2))
            ->method('request')
            ->withConsecutive(
                [
                    'GET',
                    'api/v3/order',
                    static::callback(function (array $options) use ($orderId) {
                        self::assertArrayHasKey('extra', $options);
                        self::assertArrayHasKey('body', $options);
                        self::assertSame(['security_type' => 'TRADE'], $options['extra']);
                        self::assertArrayHasKey('symbol', $options['body']);
                        self::assertSame($this->tradingPair, $options['body']['symbol']);
                        self::assertArrayHasKey('orderId', $options['body']);
                        self::assertSame($orderId, $options['body']['orderId']);

                        return true;
                    }),
                ],
                [
                    'GET',
                    'api/v3/myTrades',
                    static::callback(function (array $options) use ($time) {
                        self::assertArrayHasKey('extra', $options);
                        self::assertArrayHasKey('body', $options);
                        self::assertSame(['security_type' => 'USER_DATA'], $options['extra']);
                        self::assertArrayHasKey('symbol', $options['body']);
                        self::assertSame($this->tradingPair, $options['body']['symbol']);
                        self::assertArrayHasKey('startTime', $options['body']);
                        self::assertSame($time, $options['body']['startTime']);

                        return true;
                    }),
                ],
            )
            ->willReturnOnConsecutiveCalls($getResponse, $infoResponse)
        ;

        $result = $this->service->checkIfOrderIsFilled($orderId);

        static::assertSame(400000, $result->getAmountInSatoshis());
        static::assertSame(30000, $result->getFeesInSatoshis());
        static::assertSame('0.004 BTC', $result->getDisplayAmountBought());
        static::assertSame($amount.' '.$this->baseCurrency, $result->getDisplayAmountSpent());
        static::assertSame('1500.5 '.$this->baseCurrency, $result->getDisplayAveragePrice());
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
            'executedQty' => '0.004',
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
                'api/v3/order',
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
                'api/v3/order',
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

<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Exception\KrakenClientException;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Service\Kraken\KrakenBuyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Kraken\KrakenBuyService
 * @covers ::__construct
 *
 * @internal
 */
final class KrakenBuyServiceTest extends TestCase
{
    /** @var KrakenClientInterface|MockObject */
    protected $client;
    protected string $baseCurrency;
    protected KrakenBuyService $buyService;

    protected function setUp(): void
    {
        $this->client = $this->createMock(KrakenClientInterface::class);
        $this->baseCurrency = 'EUR'.random_int(1, 9);
        $this->buyService = new KrakenBuyService($this->client, $this->baseCurrency);
    }

    /**
     * @covers ::supportsExchange
     */
    public function testSupportsExchange(): void
    {
        static::assertTrue($this->buyService->supportsExchange('kraken'));
        static::assertFalse($this->buyService->supportsExchange('something_else'));
    }

    /**
     * @covers ::checkIfOrderIsFilled
     * @covers ::getCompletedBuyOrder
     * @covers ::getCurrentPrice
     * @covers ::initiateBuy
     *
     * @throws \Exception
     */
    public function testSuccessfulBuy(): void
    {
        $amount = random_int(100, 150);
        $price = (string) random_int(10000, 90000);
        $txId = (string) random_int(1000, 2000);
        $userRef = null;
        $fee = (string) ($amount * 0.0025);

        $this->client
            ->expects(static::once())
            ->method('queryPublic')
            ->with('Ticker', ['pair' => 'XBT'.$this->baseCurrency])
            ->willReturn(['XBT' => ['a' => [$price]]])
        ;

        $this->client
            ->expects(static::exactly(3))
            ->method('queryPrivate')
            ->withConsecutive(
                [
                    'AddOrder',
                    static::callback(function ($options) use ($price, $amount, &$userRef) {
                        self::assertArrayHasKey('pair', $options);
                        self::assertSame('XBT'.$this->baseCurrency, $options['pair']);
                        self::assertArrayHasKey('type', $options);
                        self::assertSame('buy', $options['type']);
                        self::assertArrayHasKey('ordertype', $options);
                        self::assertSame('market', $options['ordertype']);
                        self::assertArrayHasKey('volume', $options);
                        self::assertSame(bcdiv((string) $amount, $price, 8), $options['volume']);
                        self::assertArrayHasKey('oflags', $options);
                        self::assertSame('fciq', $options['oflags']);
                        self::assertArrayHasKey('userref', $options);
                        self::assertNotEmpty($options['userref']);

                        $userRef = $options['userref'];

                        return true;
                    }),
                ],
                [
                    'OpenOrders',
                    ['userref' => &$userRef],
                ],
                [
                    'TradesHistory',
                ],
            )
            ->willReturnOnConsecutiveCalls(
                ['txid' => [$txId]], // add order call
                [[]], // open orders call
                [
                    'trades' => [
                        [
                            'ordertxid' => $txId,
                            'vol' => bcdiv((string) $amount, $price, 8),
                            'cost' => $amount,
                            'price' => $price,
                            'fee' => $fee,
                        ],
                    ],
                ]
            )
        ;

        $completedOrder = $this->buyService->initiateBuy($amount);

        static::assertSame(
            (int) bcmul(bcdiv((string) $amount, $price, 8), Bitcoin::SATOSHIS, 0),
            $completedOrder->getAmountInSatoshis()
        );

        static::assertSame(bcdiv((string) $amount, $price, 8).' BTC', $completedOrder->getDisplayAmountBought());
        static::assertSame($amount.' '.$this->baseCurrency, $completedOrder->getDisplayAmountSpent());
        static::assertSame($price.' '.$this->baseCurrency, $completedOrder->getDisplayAveragePrice());
        static::assertSame($fee.' '.$this->baseCurrency, $completedOrder->getDisplayFeesSpent());
    }

    /**
     * @covers ::checkIfOrderIsFilled
     *
     * @throws PendingBuyOrderException
     */
    public function testBuyIsStillOpen(): void
    {
        $orderId = (string) random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('queryPrivate')
            ->with('OpenOrders', ['userref' => null])
            ->willReturn(['open' => ['order is open']])
        ;

        $this->expectException(PendingBuyOrderException::class);

        $this->buyService->checkIfOrderIsFilled($orderId);
    }

    /**
     * @covers ::cancelBuyOrder
     *
     * @throws \Exception
     */
    public function testBuyIsCancelled(): void
    {
        $orderId = (string) random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('queryPrivate')
            ->with('CancelOrder', ['txid' => $orderId])
        ;

        $this->buyService->cancelBuyOrder($orderId);
    }

    /**
     * @covers ::checkIfOrderIsFilled
     * @covers ::getCompletedBuyOrder
     * @covers ::getCurrentPrice
     * @covers ::initiateBuy
     *
     * @throws \Exception
     */
    public function testBuyCannotBeLocatedAfterPurchase(): void
    {
        $amount = random_int(100000, 900000);
        $price = (string) random_int(10000, 90000);
        $txId = (string) random_int(1000, 2000);
        $userRef = null;

        $this->client
            ->expects(static::once())
            ->method('queryPublic')
            ->with('Ticker', ['pair' => 'XBT'.$this->baseCurrency])
            ->willReturn(['XBT' => ['a' => [$price]]])
        ;

        $this->client
            ->expects(static::exactly(3))
            ->method('queryPrivate')
            ->withConsecutive(
                [
                    'AddOrder',
                    static::callback(function ($options) use (&$userRef) {
                        self::assertArrayHasKey('userref', $options);
                        self::assertNotEmpty($options['userref']);

                        $userRef = $options['userref'];

                        return true;
                    }),
                ],
                [
                    'OpenOrders',
                    ['userref' => &$userRef],
                ],
                [
                    'TradesHistory',
                ],
            )
            ->willReturnOnConsecutiveCalls(
                ['txid' => [$txId]], // add order call
                [[]], // open orders call
                [['trades' => []]]
            )
        ;

        $this->expectException(KrakenClientException::class);
        $this->expectExceptionMessage('no open orders left yet order was not found, you should investigate this');

        $this->buyService->initiateBuy($amount);
    }
}

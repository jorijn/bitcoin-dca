<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Service;

use Jorijn\Bl3pDca\Client\Bl3pClientInterface;
use Jorijn\Bl3pDca\Event\BuySuccessEvent;
use Jorijn\Bl3pDca\Exception\BuyTimeoutException;
use Jorijn\Bl3pDca\Service\BuyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Service\BuyService
 * @covers ::__construct
 *
 * @internal
 */
final class BuyServiceTest extends TestCase
{
    /** @var Bl3pClientInterface|MockObject */
    private $client;
    /** @var LoggerInterface|MockObject */
    private $logger;
    /** @var EventDispatcherInterface|MockObject */
    private $dispatcher;
    private BuyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Bl3pClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new BuyService(
            $this->client,
            $this->dispatcher,
            $this->logger,
            5
        );
    }

    public function providerOfBuyScenarios(): array
    {
        return [
            'buy fills immediately' => [0, false],
            'buy fills after three seconds' => [3, false],
            'buy fills after seven seconds, but timeout is set at 5 -> timeout + cancellation' => [7, true],
            'buy fills immediately but fees settled in EUR' => [0, false, 'EUR'],
        ];
    }

    /**
     * @covers ::buy
     * @dataProvider providerOfBuyScenarios
     *
     * @throws \Exception
     */
    public function testBuyingWithVariousOptions(
        int $fillDelayed = 0,
        bool $expectCancellation = false,
        string $feeCurrency = 'BTC'
    ): void {
        $amount = random_int(5, 10);
        $buyParams = $this->createBuy($amount);
        $orderId = random_int(1000, 2000);
        $tag = 't'.random_int(1000, 2000);

        $buyResult = $this->getNewOrderResult($orderId);
        $delayedResult = $this->getDelayedOrderResult();

        [
            $amountBought,
            $amountBoughtDisplayed,
            $feesDisplayed,
            $feeSpent,
            $totalSpentDisplayed,
            $averageCostDisplayed,
            $closedResult,
        ] = $this->getClosedOrderResult($feeCurrency);

        $startingTime = time();
        $orderClosedAt = $startingTime + $fillDelayed;
        $attemptedBuy = $attemptedDelayedCall = $attemptedResultCall = $attemptedCancellation = false;

        $this->client
            ->expects(static::atLeastOnce())
            ->method('apiCall')
            ->willReturnCallback(function (string $url, array $parameters) use (
                $orderId,
                $buyParams,
                $closedResult,
                $delayedResult,
                $orderClosedAt,
                $buyResult,
                &$attemptedBuy,
                &$attemptedDelayedCall,
                &$attemptedResultCall,
                &$attemptedCancellation
            ) {
                $returnValue = [];

                switch ($url) {
                    case 'BTCEUR/money/order/add':
                        $attemptedBuy = true;

                        self::assertArrayHasKey(BuyService::TYPE, $parameters);
                        self::assertSame($buyParams[BuyService::TYPE], $parameters[BuyService::TYPE]);
                        self::assertArrayHasKey(BuyService::AMOUNT_FUNDS_INT, $parameters);
                        self::assertSame(
                            $buyParams[BuyService::AMOUNT_FUNDS_INT],
                            $parameters[BuyService::AMOUNT_FUNDS_INT]
                        );
                        self::assertArrayHasKey(BuyService::FEE_CURRENCY, $parameters);
                        self::assertSame($buyParams[BuyService::FEE_CURRENCY], $parameters[BuyService::FEE_CURRENCY]);

                        $returnValue = $buyResult;

                        break;
                    case 'BTCEUR/money/order/result':
                        self::assertArrayHasKey(BuyService::ORDER_ID, $parameters);
                        self::assertSame($orderId, $parameters[BuyService::ORDER_ID]);

                        if (time() < $orderClosedAt) {
                            $attemptedDelayedCall = true;

                            return $delayedResult;
                        }

                        $attemptedResultCall = true;

                        $returnValue = $closedResult;

                        break;
                    case 'BTCEUR/money/order/cancel':
                        $attemptedCancellation = true;

                        self::assertArrayHasKey(BuyService::ORDER_ID, $parameters);
                        self::assertSame($orderId, $parameters[BuyService::ORDER_ID]);

                        break;
                    default:
                        $this->addToAssertionCount(1);

                        throw new BuyServiceTestException('did not expect call to location '.$url);
                }

                return $returnValue;
            })
        ;

        $returnedBuyOrder = null;
        $this->dispatcher
            ->expects($expectCancellation ? static::never() : static::once())
            ->method('dispatch')
            ->with(static::callback(static function (BuySuccessEvent $event) use ($tag, &$returnedBuyOrder) {
                self::assertSame($tag, $event->getTag());
                $returnedBuyOrder = $event->getBuyOrder();

                return true;
            }))
        ;

        if ($expectCancellation) {
            $this->expectException(BuyTimeoutException::class);
        }

        $completedBuyOrder = $this->service->buy($amount, $tag);

        static::assertTrue($attemptedBuy);
        static::assertSame($fillDelayed > 0, $attemptedDelayedCall);
        static::assertSame(!$expectCancellation, $attemptedResultCall);
        static::assertSame($expectCancellation, $attemptedCancellation);

        if (!$expectCancellation) {
            static::assertSame($returnedBuyOrder, $completedBuyOrder);
            static::assertSame($amountBought, $completedBuyOrder->getAmountInSatoshis());
            static::assertSame('BTC' === $feeCurrency ? $feeSpent : 0, $completedBuyOrder->getFeesInSatoshis());
            static::assertSame($amountBoughtDisplayed, $completedBuyOrder->getDisplayAmountBought());
            static::assertSame($totalSpentDisplayed, $completedBuyOrder->getDisplayAmountSpent());
            static::assertSame($averageCostDisplayed, $completedBuyOrder->getDisplayAveragePrice());
            static::assertSame($feesDisplayed, $completedBuyOrder->getDisplayFeesSpent());
        }
    }

    protected function createBuy(int $amount): array
    {
        return [
            BuyService::TYPE => 'bid',
            BuyService::AMOUNT_FUNDS_INT => $amount * 100000,
            BuyService::FEE_CURRENCY => 'BTC',
        ];
    }

    /**
     * @throws \Exception
     */
    protected function getClosedOrderResult(string $feeCurrency): array
    {
        $closedResult = [
            BuyService::DATA => [
                BuyService::STATUS => BuyService::ORDER_STATUS_CLOSED,
                BuyService::TOTAL_AMOUNT => [
                    BuyService::VALUE_INT => $amountBought = random_int(1000, 2000),
                    BuyService::DISPLAY => $amountBoughtDisplayed = $amountBought.' BTC',
                ],
                BuyService::TOTAL_FEE => [
                    BuyService::CURRENCY => $feeCurrency,
                    BuyService::DISPLAY => $feesDisplayed = random_int(1000, 2000).' '.$feeCurrency,
                    BuyService::VALUE_INT => $feeSpent = random_int(1000, 2000),
                ],
                BuyService::TOTAL_SPENT => [
                    BuyService::DISPLAY_SHORT => $totalSpentDisplayed = random_int(1000, 2000).' EUR',
                ],
                BuyService::AVG_COST => [
                    BuyService::DISPLAY_SHORT => $averageCostDisplayed = random_int(1000, 2000).' EUR',
                ],
            ],
        ];

        return [
            $amountBought,
            $amountBoughtDisplayed,
            $feesDisplayed,
            $feeSpent,
            $totalSpentDisplayed,
            $averageCostDisplayed,
            $closedResult,
        ];
    }

    /**
     * @return \string[][]
     */
    protected function getDelayedOrderResult(): array
    {
        return [
            BuyService::DATA => [
                BuyService::STATUS => 'open',
            ],
        ];
    }

    /**
     * @return \int[][]
     */
    protected function getNewOrderResult(int $orderId): array
    {
        return [
            BuyService::DATA => [
                BuyService::ORDER_ID => $orderId,
            ],
        ];
    }
}

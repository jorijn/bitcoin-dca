<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Bl3p;

use Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface;
use Jorijn\Bitcoin\Dca\Exception\BuyTimeoutException;
use Jorijn\Bitcoin\Dca\Service\Bl3p\Bl3pBuyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Tests\Jorijn\Bitcoin\Dca\Service\BuyServiceTestException;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Bl3p\Bl3pBuyService
 * @covers ::__construct
 *
 * @internal
 */
final class Bl3pBuyServiceTest extends TestCase
{
    /** @var Bl3pClientInterface|MockObject */
    private $client;
    /** @var LoggerInterface|MockObject */
    private $logger;
    /** @var EventDispatcherInterface|MockObject */
    private $dispatcher;
    private Bl3pBuyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Bl3pClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new Bl3pBuyService($this->client, $this->logger);
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
        $baseCurrency = 'E'.random_int(1000, 2000);

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
                $baseCurrency,
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
                    case 'BTC'.$baseCurrency.'/money/order/add':
                        $attemptedBuy = true;

                        self::assertArrayHasKey(Bl3pBuyService::TYPE, $parameters);
                        self::assertSame($buyParams[Bl3pBuyService::TYPE], $parameters[Bl3pBuyService::TYPE]);
                        self::assertArrayHasKey(Bl3pBuyService::AMOUNT_FUNDS_INT, $parameters);
                        self::assertSame(
                            $buyParams[Bl3pBuyService::AMOUNT_FUNDS_INT],
                            $parameters[Bl3pBuyService::AMOUNT_FUNDS_INT]
                        );
                        self::assertArrayHasKey(Bl3pBuyService::FEE_CURRENCY, $parameters);
                        self::assertSame($buyParams[Bl3pBuyService::FEE_CURRENCY], $parameters[Bl3pBuyService::FEE_CURRENCY]);

                        $returnValue = $buyResult;

                        break;
                    case 'BTC'.$baseCurrency.'/money/order/result':
                        self::assertArrayHasKey(Bl3pBuyService::ORDER_ID, $parameters);
                        self::assertSame($orderId, $parameters[Bl3pBuyService::ORDER_ID]);

                        if (time() < $orderClosedAt) {
                            $attemptedDelayedCall = true;

                            return $delayedResult;
                        }

                        $attemptedResultCall = true;

                        $returnValue = $closedResult;

                        break;
                    case 'BTC'.$baseCurrency.'/money/order/cancel':
                        $attemptedCancellation = true;

                        self::assertArrayHasKey(Bl3pBuyService::ORDER_ID, $parameters);
                        self::assertSame($orderId, $parameters[Bl3pBuyService::ORDER_ID]);

                        break;
                    default:
                        $this->addToAssertionCount(1);

                        throw new BuyServiceTestException('did not expect call to location '.$url);
                }

                return $returnValue;
            })
        ;

        if ($expectCancellation) {
            $this->expectException(BuyTimeoutException::class);
        }

        $completedBuyOrder = $this->service->buy($amount, $baseCurrency, 5);

        static::assertTrue($attemptedBuy);
        static::assertSame($fillDelayed > 0, $attemptedDelayedCall);
        static::assertSame(!$expectCancellation, $attemptedResultCall);
        static::assertSame($expectCancellation, $attemptedCancellation);

        if (!$expectCancellation) {
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
            Bl3pBuyService::TYPE => 'bid',
            Bl3pBuyService::AMOUNT_FUNDS_INT => $amount * 100000,
            Bl3pBuyService::FEE_CURRENCY => 'BTC',
        ];
    }

    /**
     * @throws \Exception
     */
    protected function getClosedOrderResult(string $feeCurrency): array
    {
        $closedResult = [
            Bl3pBuyService::DATA => [
                Bl3pBuyService::STATUS => Bl3pBuyService::ORDER_STATUS_CLOSED,
                Bl3pBuyService::TOTAL_AMOUNT => [
                    Bl3pBuyService::VALUE_INT => $amountBought = random_int(1000, 2000),
                    Bl3pBuyService::DISPLAY => $amountBoughtDisplayed = $amountBought.' BTC',
                ],
                Bl3pBuyService::TOTAL_FEE => [
                    Bl3pBuyService::CURRENCY => $feeCurrency,
                    Bl3pBuyService::DISPLAY => $feesDisplayed = random_int(1000, 2000).' '.$feeCurrency,
                    Bl3pBuyService::VALUE_INT => $feeSpent = random_int(1000, 2000),
                ],
                Bl3pBuyService::TOTAL_SPENT => [
                    Bl3pBuyService::DISPLAY_SHORT => $totalSpentDisplayed = random_int(1000, 2000).' EUR',
                ],
                Bl3pBuyService::AVG_COST => [
                    Bl3pBuyService::DISPLAY_SHORT => $averageCostDisplayed = random_int(1000, 2000).' EUR',
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
            Bl3pBuyService::DATA => [
                Bl3pBuyService::STATUS => 'open',
            ],
        ];
    }

    /**
     * @return \int[][]
     */
    protected function getNewOrderResult(int $orderId): array
    {
        return [
            Bl3pBuyService::DATA => [
                Bl3pBuyService::ORDER_ID => $orderId,
            ],
        ];
    }
}

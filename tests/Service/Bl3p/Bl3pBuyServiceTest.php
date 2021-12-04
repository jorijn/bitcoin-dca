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

namespace Tests\Jorijn\Bitcoin\Dca\Service\Bl3p;

use Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Service\Bl3p\Bl3pBuyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Bl3p\Bl3pBuyService
 * @covers ::__construct
 *
 * @internal
 */
final class Bl3pBuyServiceTest extends TestCase
{
    private const AMOUNT_BOUGHT = 'amountBought';
    private const AMOUNT_BOUGHT_DISPLAYED = 'amountBoughtDisplayed';
    private const FEES_DISPLAYED = 'feesDisplayed';
    private const FEE_SPENT = 'feeSpent';
    private const TOTAL_SPENT_DISPLAYED = 'totalSpentDisplayed';
    private const AVERAGE_COST_DISPLAYED = 'averageCostDisplayed';
    private const CLOSED_RESULT = 'closedResult';
    private const API_CALL = 'apiCall';
    private const BTC = 'BTC';
    private const MONEY_ORDER_RESULT = '/money/order/result';
    private const MONEY_ORDER_ADD = '/money/order/add';
    /** @var Bl3pClientInterface|MockObject */
    private $client;
    private string $baseCurrency;
    private Bl3pBuyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Bl3pClientInterface::class);
        $this->baseCurrency = (string) random_int(111, 999);
        $this->service = new Bl3pBuyService($this->client, $this->baseCurrency);
    }

    public function providerOfFeeCurrencies(): array
    {
        return [
            'Not BTC' => ['EUR'],
            self::BTC => [self::BTC],
        ];
    }

    /**
     * @covers ::supportsExchange
     */
    public function testSupportsExchange(): void
    {
        static::assertTrue($this->service->supportsExchange('bl3p'));
        static::assertFalse($this->service->supportsExchange('bl4p'));
    }

    /**
     * @covers ::checkIfOrderIsFilled
     * @covers ::initiateBuy
     *
     * @throws PendingBuyOrderException
     *
     * @dataProvider providerOfFeeCurrencies
     *
     * @throws PendingBuyOrderException
     */
    public function testInitiateBuyFillsDirectly(string $feeCurrency): void
    {
        $amount = random_int(10, 20);
        $orderId = 'oid'.random_int(1000, 2000);

        [
            self::AMOUNT_BOUGHT => $amountBought,
            self::AMOUNT_BOUGHT_DISPLAYED => $amountBoughtDisplayed,
            self::FEES_DISPLAYED => $feesDisplayed,
            self::FEE_SPENT => $feeSpent,
            self::TOTAL_SPENT_DISPLAYED => $totalSpentDisplayed,
            self::AVERAGE_COST_DISPLAYED => $averageCostDisplayed,
            self::CLOSED_RESULT => $closedResult,
        ] = $this->getClosedOrderResult($feeCurrency);

        $this->client
            ->expects(static::exactly(2))
            ->method(self::API_CALL)
            ->withConsecutive(
                [
                    self::BTC.$this->baseCurrency.self::MONEY_ORDER_ADD,
                    [
                        Bl3pBuyService::TYPE => 'bid',
                        Bl3pBuyService::AMOUNT_FUNDS_INT => $amount * 100000,
                        Bl3pBuyService::FEE_CURRENCY => self::BTC,
                    ],
                ],
                [
                    self::BTC.$this->baseCurrency.self::MONEY_ORDER_RESULT,
                    [
                        Bl3pBuyService::ORDER_ID => $orderId,
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls($this->getNewOrderResult($orderId), $closedResult)
        ;

        $completedBuyDTO = $this->service->initiateBuy($amount);

        static::assertSame($amountBought, $completedBuyDTO->getAmountInSatoshis());
        static::assertSame($amountBoughtDisplayed, $completedBuyDTO->getDisplayAmountBought());
        static::assertSame($feesDisplayed, $completedBuyDTO->getDisplayFeesSpent());
        static::assertSame(self::BTC === $feeCurrency ? $feeSpent : 0, $completedBuyDTO->getFeesInSatoshis());
        static::assertSame($totalSpentDisplayed, $completedBuyDTO->getDisplayAmountSpent());
        static::assertSame($averageCostDisplayed, $completedBuyDTO->getDisplayAveragePrice());
    }

    /**
     * @covers ::checkIfOrderIsFilled
     * @covers ::initiateBuy
     *
     * @throws PendingBuyOrderException
     */
    public function testInitiateBuyButDoesNotFillDirectly(): void
    {
        $amount = random_int(10, 20);
        $orderId = 'oid'.random_int(1000, 2000);

        $this->client
            ->expects(static::exactly(2))
            ->method(self::API_CALL)
            ->withConsecutive(
                [self::BTC.$this->baseCurrency.self::MONEY_ORDER_ADD],
                [self::BTC.$this->baseCurrency.self::MONEY_ORDER_RESULT]
            )
            ->willReturnOnConsecutiveCalls($this->getNewOrderResult($orderId), $this->getPendingOrderResult())
        ;

        $this->expectException(PendingBuyOrderException::class);

        $this->service->initiateBuy($amount);
    }

    /**
     * @covers ::checkIfOrderIsFilled
     *
     * @throws PendingBuyOrderException
     */
    public function testCheckIfOrderIsFilledButStillPending(): void
    {
        $orderId = 'oid'.random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method(self::API_CALL)
            ->with(self::BTC.$this->baseCurrency.self::MONEY_ORDER_RESULT, [Bl3pBuyService::ORDER_ID => $orderId])
            ->willReturn($this->getPendingOrderResult())
        ;

        $this->expectException(PendingBuyOrderException::class);

        $this->service->checkIfOrderIsFilled($orderId);
    }

    /**
     * @covers ::checkIfOrderIsFilled
     * @covers ::initiateBuy
     *
     * @throws PendingBuyOrderException
     *
     * @dataProvider providerOfFeeCurrencies
     *
     * @throws PendingBuyOrderException
     */
    public function testCheckIfOrderIsFilled(string $feeCurrency): void
    {
        $orderId = 'oid'.random_int(1000, 2000);

        [
            self::AMOUNT_BOUGHT => $amountBought,
            self::AMOUNT_BOUGHT_DISPLAYED => $amountBoughtDisplayed,
            self::FEES_DISPLAYED => $feesDisplayed,
            self::FEE_SPENT => $feeSpent,
            self::TOTAL_SPENT_DISPLAYED => $totalSpentDisplayed,
            self::AVERAGE_COST_DISPLAYED => $averageCostDisplayed,
            self::CLOSED_RESULT => $closedResult,
        ] = $this->getClosedOrderResult($feeCurrency);

        $this->client
            ->expects(static::once())
            ->method(self::API_CALL)
            ->with(
                self::BTC.$this->baseCurrency.self::MONEY_ORDER_RESULT,
                [Bl3pBuyService::ORDER_ID => $orderId],
            )
            ->willReturn($closedResult)
        ;

        $completedBuyDTO = $this->service->checkIfOrderIsFilled($orderId);

        static::assertSame($amountBought, $completedBuyDTO->getAmountInSatoshis());
        static::assertSame($amountBoughtDisplayed, $completedBuyDTO->getDisplayAmountBought());
        static::assertSame($feesDisplayed, $completedBuyDTO->getDisplayFeesSpent());
        static::assertSame(self::BTC === $feeCurrency ? $feeSpent : 0, $completedBuyDTO->getFeesInSatoshis());
        static::assertSame($totalSpentDisplayed, $completedBuyDTO->getDisplayAmountSpent());
        static::assertSame($averageCostDisplayed, $completedBuyDTO->getDisplayAveragePrice());
    }

    /**
     * @covers ::cancelBuyOrder
     */
    public function testCancelBuyOrder(): void
    {
        $orderId = 'oid'.random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method(self::API_CALL)
            ->with(self::BTC.$this->baseCurrency.'/money/order/cancel', [Bl3pBuyService::ORDER_ID => $orderId])
        ;

        $this->service->cancelBuyOrder($orderId);
    }

    private function getClosedOrderResult(string $feeCurrency = 'BTC'): array
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
                    Bl3pBuyService::DISPLAY_SHORT => $totalSpentDisplayed = random_int(
                        1000,
                        2000
                    ).' '.$this->baseCurrency,
                ],
                Bl3pBuyService::AVG_COST => [
                    Bl3pBuyService::DISPLAY_SHORT => $averageCostDisplayed = random_int(
                        1000,
                        2000
                    ).' '.$this->baseCurrency,
                ],
            ],
        ];

        return [
            self::AMOUNT_BOUGHT => $amountBought,
            self::AMOUNT_BOUGHT_DISPLAYED => $amountBoughtDisplayed,
            self::FEES_DISPLAYED => $feesDisplayed,
            self::FEE_SPENT => $feeSpent,
            self::TOTAL_SPENT_DISPLAYED => $totalSpentDisplayed,
            self::AVERAGE_COST_DISPLAYED => $averageCostDisplayed,
            self::CLOSED_RESULT => $closedResult,
        ];
    }

    private function getNewOrderResult(string $orderId): array
    {
        return [Bl3pBuyService::DATA => [Bl3pBuyService::ORDER_ID => $orderId]];
    }

    private function getPendingOrderResult(): array
    {
        return [Bl3pBuyService::DATA => [Bl3pBuyService::STATUS => 'open']];
    }
}

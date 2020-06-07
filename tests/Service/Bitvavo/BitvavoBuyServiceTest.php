<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Bitvavo;

use Jorijn\Bitcoin\Dca\Client\BitvavoClientInterface;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Service\Bitvavo\BitvavoBuyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Bitvavo\BitvavoBuyService
 * @covers ::__construct
 *
 * @internal
 */
final class BitvavoBuyServiceTest extends TestCase
{
    /** @var BitvavoClientInterface|MockObject */
    private $client;
    private string $baseCurrency;
    private BitvavoBuyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(BitvavoClientInterface::class);
        $this->baseCurrency = (string) random_int(111, 999);
        $this->service = new BitvavoBuyService($this->client, $this->baseCurrency);
    }

    /**
     * @covers ::getAveragePrice
     * @covers ::getCompletedBuyOrderFromResponse
     * @covers ::initiateBuy
     */
    public function testInitiateBuySucceedsDirectly(): void
    {
        $amount = random_int(10, 20);

        [
            'data' => $data,
            'filledSatoshis' => $filledSatoshis,
            'filledQuote' => $filledQuote,
            'feePaid' => $feePaid,
            'feeCurrency' => $feeCurrency,
            'price' => $price,
        ] = $this->getSimpleResponseStructure();

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('order', 'POST', [], [
                'market' => sprintf('BTC-'.$this->baseCurrency),
                'side' => 'buy',
                'orderType' => 'market',
                'amountQuote' => (string) $amount,
            ])
            ->willReturn($data)
        ;

        $responseDTO = $this->service->initiateBuy($amount);

        static::assertSame($filledSatoshis, $responseDTO->getAmountInSatoshis());
        static::assertSame($filledQuote.' '.$this->baseCurrency, $responseDTO->getDisplayAmountSpent());
        static::assertSame(0, $responseDTO->getFeesInSatoshis());
        static::assertSame($feePaid.' '.$feeCurrency, $responseDTO->getDisplayFeesSpent());
        static::assertSame($price.' '.$this->baseCurrency, $responseDTO->getDisplayAveragePrice());
    }

    /**
     * @covers ::initiateBuy
     */
    public function testInitiateBuyButIsStillPending(): void
    {
        $orderId = 'oid'.random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('order', 'POST', [], [
                'market' => sprintf('BTC-'.$this->baseCurrency),
                'side' => 'buy',
                'orderType' => 'market',
                'amountQuote' => (string) 1,
            ])
            ->willReturn($this->getPendingResponseStructure($orderId))
        ;

        $this->expectExceptionObject(new PendingBuyOrderException($orderId));

        $this->service->initiateBuy(1);
    }

    /**
     * @covers ::checkIfOrderIsFilled
     * @covers ::getAveragePrice
     * @covers ::getCompletedBuyOrderFromResponse
     */
    public function testCheckIsFilled(): void
    {
        $orderId = 'oid'.random_int(1000, 2000);

        [
            'data' => $data,
            'filledSatoshis' => $filledSatoshis,
            'filledQuote' => $filledQuote,
            'feePaid' => $feePaid,
            'feeCurrency' => $feeCurrency,
            'price' => $price,
        ] = $this->getSimpleResponseStructure();

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('order', 'GET', [
                'market' => sprintf('BTC-'.$this->baseCurrency),
                'orderId' => $orderId,
            ])
            ->willReturn($data)
        ;

        $responseDTO = $this->service->checkIfOrderIsFilled($orderId);

        static::assertSame($filledSatoshis, $responseDTO->getAmountInSatoshis());
        static::assertSame($filledQuote.' '.$this->baseCurrency, $responseDTO->getDisplayAmountSpent());
        static::assertSame(0, $responseDTO->getFeesInSatoshis());
        static::assertSame($feePaid.' '.$feeCurrency, $responseDTO->getDisplayFeesSpent());
        static::assertSame($price.' '.$this->baseCurrency, $responseDTO->getDisplayAveragePrice());
    }

    /**
     * @covers ::checkIfOrderIsFilled
     */
    public function testCheckNotYetFilled(): void
    {
        $orderId = 'oid'.random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('order', 'GET', [
                'market' => sprintf('BTC-'.$this->baseCurrency),
                'orderId' => $orderId,
            ])
            ->willReturn($this->getPendingResponseStructure($orderId))
        ;

        $this->expectExceptionObject(new PendingBuyOrderException($orderId));

        $this->service->checkIfOrderIsFilled($orderId);
    }

    /**
     * @covers ::cancelBuyOrder
     */
    public function testCancelOrder(): void
    {
        $orderId = 'oid'.random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('order', 'DELETE', [
                'market' => 'BTC-'.$this->baseCurrency,
                'orderId' => $orderId,
            ])
        ;

        $this->service->cancelBuyOrder($orderId);
    }

    /**
     * @covers ::getAveragePrice
     * @covers ::getCompletedBuyOrderFromResponse
     * @covers ::initiateBuy
     */
    public function testFeesAreAccountedInBitcoin(): void
    {
        $amount = random_int(10, 20);

        [
            'data' => $data,
            'feePaid' => $feePaid,
            'feeCurrency' => $feeCurrency,
        ] = $this->getSimpleResponseStructure('BTC');

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->willReturn($data)
        ;

        $responseDTO = $this->service->initiateBuy($amount);

        static::assertSame($feePaid * 100000000, $responseDTO->getFeesInSatoshis());
        static::assertSame($feePaid.' '.$feeCurrency, $responseDTO->getDisplayFeesSpent());
    }

    /**
     * @covers ::supportsExchange
     */
    public function testSupportsExchange(): void
    {
        static::assertTrue($this->service->supportsExchange('bitvavo'));
        static::assertFalse($this->service->supportsExchange('bitvivo'));
    }

    private function getSimpleResponseStructure($feeCurrency = 'EUR'): array
    {
        $price = random_int(9000, 11000);

        $data = [
            'filledAmount' => ($filledSatoshis = random_int(10000, 20000)) / 100000000,
            'filledAmountQuote' => $filledQuote = random_int(10, 20),
            'status' => 'filled',
            'amountQuote' => $filledQuote,
            'feePaid' => $feePaid = random_int(1, 10),
            'feeCurrency' => $feeCurrency,
            'fills' => [
                $this->createFill($filledSatoshis / 100000000, 50, $price - 1000),
                $this->createFill($filledSatoshis / 100000000, 50, $price + 1000),
            ],
        ];

        return [
            'data' => $data,
            'filledSatoshis' => $filledSatoshis,
            'filledQuote' => $filledQuote,
            'feePaid' => $feePaid,
            'feeCurrency' => $feeCurrency,
            'price' => $price,
        ];
    }

    private function getPendingResponseStructure(string $orderId): array
    {
        return [
            'orderId' => $orderId,
            'status' => 'open',
        ];
    }

    private function createFill(float $totalAmount, int $percentage, int $price): array
    {
        return [
            'amount' => ($totalAmount / 100) * $percentage,
            'price' => $price,
        ];
    }
}

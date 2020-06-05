<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Bitvavo;

use Jorijn\Bitcoin\Dca\Client\BitvavoClientInterface;
use Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface;
use Jorijn\Bitcoin\Dca\Service\Bitvavo\BitvavoBalanceService;
use Jorijn\Bitcoin\Dca\Service\Bl3p\Bl3pBalanceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Bitvavo\BitvavoBalanceService
 * @covers ::__construct
 *
 * @internal
 */
final class BitvavoBalanceServiceTest extends TestCase
{
    /** @var BitvavoClientInterface|MockObject */
    private $client;
    private BitvavoBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(BitvavoClientInterface::class);
        $this->service = new BitvavoBalanceService($this->client);
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
     * @covers ::getBalances
     */
    public function testShowsBalance(): void
    {
        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('balance')
            ->willReturn($this->getStubResponse())
        ;

        $result = $this->service->getBalances();

        static::assertSame([
            ['BTC', '3 BTC', '1 BTC'],
            ['EUR', '2 EUR', '2 EUR'],
        ], $result);
    }

    protected function getStubResponse(): array
    {
        return [
            ['symbol' => 'BTC', 'available' => 3, 'inOrder' => 2],
            ['symbol' => 'EUR', 'available' => 2, 'inOrder' => 0],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Bl3p;

use Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface;
use Jorijn\Bitcoin\Dca\Service\Bl3p\Bl3pBalanceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Bl3p\Bl3pBalanceService
 *
 * @internal
 */
final class Bl3pBalanceServiceTest extends TestCase
{
    /** @var Bl3pClientInterface|MockObject */
    private $client;
    private Bl3pBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Bl3pClientInterface::class);
        $this->service = new Bl3pBalanceService($this->client);
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
     * @covers ::getBalances
     */
    public function testShowsBalance(): void
    {
        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('GENMKT/money/info')
            ->willReturn($this->getStubResponse())
        ;

        $result = $this->service->getBalances();

        static::assertSame([
            ['BTC', '1 BTC', '1 BTC'],
            ['EUR', '2 EUR', '2 EUR'],
        ], $result);
    }

    protected function getStubResponse(): array
    {
        return [
            'data' => [
                'wallets' => [
                    'BTC' => [
                        'balance' => [
                            'display' => '1 BTC',
                            'value_int' => 1,
                        ],
                        'available' => [
                            'display' => '1 BTC',
                            'value_int' => 1,
                        ],
                    ],
                    'EUR' => [
                        'balance' => [
                            'display' => '2 EUR',
                            'value_int' => 2,
                        ],
                        'available' => [
                            'display' => '2 EUR',
                            'value_int' => 2,
                        ],
                    ],
                    'LTC' => [
                        'balance' => [
                            'display' => '0 LTC',
                            'value_int' => 0,
                        ],
                        'available' => [
                            'display' => '0 LTC',
                            'value_int' => 0,
                        ],
                    ],
                ],
            ],
        ];
    }
}

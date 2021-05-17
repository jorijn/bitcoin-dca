<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Binance;

use Jorijn\Bitcoin\Dca\Client\BinanceClientInterface;
use Jorijn\Bitcoin\Dca\Service\Binance\BinanceBalanceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Binance\BinanceBalanceService
 * @covers ::__construct
 *
 * @internal
 */
final class BinanceBalanceServiceTest extends TestCase
{
    /** @var BinanceClientInterface|MockObject */
    protected $client;
    protected BinanceBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(BinanceClientInterface::class);
        $this->service = new BinanceBalanceService($this->client);
    }

    /**
     * @covers ::getBalances
     */
    public function testGetBalances(): void
    {
        $responseStub = [
            'balances' => [
                ['asset' => 'BTC', 'free' => '1.001', 'locked' => '1.001'],
                ['asset' => 'XRP', 'free' => '0.000', 'locked' => '0.000'], // as it should be
            ],
        ];

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'GET',
                'api/v3/account',
                static::callback(function (array $extra) {
                    self::assertArrayHasKey('extra', $extra);
                    self::assertArrayHasKey('security_type', $extra['extra']);
                    self::assertSame('USER_DATA', $extra['extra']['security_type']);

                    return true;
                })
            )
            ->willReturn($responseStub)
        ;

        $response = $this->service->getBalances();

        static::assertArrayHasKey('BTC', $response);
        static::assertArrayNotHasKey('XRP', $response);

        static::assertSame(['BTC', '2.002', '1.001'], $response['BTC']);
    }

    /**
     * @covers ::supportsExchange
     */
    public function testSupportsExchange(): void
    {
        static::assertTrue($this->service->supportsExchange('binance'));
        static::assertFalse($this->service->supportsExchange('kraken'));
    }
}

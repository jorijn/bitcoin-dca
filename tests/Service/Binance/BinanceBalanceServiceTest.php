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

namespace Tests\Jorijn\Bitcoin\Dca\Service\Binance;

use Jorijn\Bitcoin\Dca\Client\BinanceClientInterface;
use Jorijn\Bitcoin\Dca\Service\Binance\BinanceBalanceService;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Binance\BinanceBalanceService
 *
 * @covers ::__construct
 *
 * @internal
 */
final class BinanceBalanceServiceTest extends TestCase
{
    protected \Jorijn\Bitcoin\Dca\Client\BinanceClientInterface|\PHPUnit\Framework\MockObject\MockObject $client;
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
                ['asset' => 'BTC', 'free' => '1.001', 'locked' => '1.002'],
                ['asset' => 'XRP', 'free' => '0.000', 'locked' => '0.000'], // as it should be
            ],
        ];

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'GET',
                'api/v3/account',
                static::callback(function (array $extra): bool {
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

        static::assertSame(['BTC', '2.003', '1.001'], $response['BTC']);
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

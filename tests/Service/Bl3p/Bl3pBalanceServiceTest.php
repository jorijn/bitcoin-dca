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
use Jorijn\Bitcoin\Dca\Service\Bl3p\Bl3pBalanceService;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Bl3p\Bl3pBalanceService
 *
 * @covers ::__construct
 *
 * @internal
 */
final class Bl3pBalanceServiceTest extends TestCase
{
    private const ONE_BTC = '1 BTC';
    private const TWO_EURO = '2 EUR';
    private const BALANCE = 'balance';
    private const DISPLAY = 'display';
    private const VALUE_INT = 'value_int';
    private const AVAILABLE = 'available';

    private \Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface|\PHPUnit\Framework\MockObject\MockObject $client;
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
            ['BTC', self::ONE_BTC, self::ONE_BTC],
            ['EUR', self::TWO_EURO, self::TWO_EURO],
        ], $result);
    }

    protected function getStubResponse(): array
    {
        return [
            'data' => [
                'wallets' => [
                    'BTC' => [
                        self::BALANCE => [
                            self::DISPLAY => self::ONE_BTC,
                            self::VALUE_INT => 1,
                        ],
                        self::AVAILABLE => [
                            self::DISPLAY => self::ONE_BTC,
                            self::VALUE_INT => 1,
                        ],
                    ],
                    'EUR' => [
                        self::BALANCE => [
                            self::DISPLAY => self::TWO_EURO,
                            self::VALUE_INT => 2,
                        ],
                        self::AVAILABLE => [
                            self::DISPLAY => self::TWO_EURO,
                            self::VALUE_INT => 2,
                        ],
                    ],
                    'LTC' => [
                        self::BALANCE => [
                            self::DISPLAY => '0 LTC',
                            self::VALUE_INT => 0,
                        ],
                        self::AVAILABLE => [
                            self::DISPLAY => '0 LTC',
                            self::VALUE_INT => 0,
                        ],
                    ],
                ],
            ],
        ];
    }
}

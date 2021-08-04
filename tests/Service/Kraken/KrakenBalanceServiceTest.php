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

namespace Tests\Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Service\Kraken\KrakenBalanceService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Kraken\KrakenBalanceService
 * @covers ::__construct
 *
 * @internal
 */
final class KrakenBalanceServiceTest extends TestCase
{
    /** @var KrakenClientInterface|MockObject */
    protected $client;

    protected KrakenBalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(KrakenClientInterface::class);
        $this->balanceService = new KrakenBalanceService($this->client);
    }

    /**
     * @covers ::supportsExchange
     */
    public function testSupportsExchange(): void
    {
        static::assertTrue($this->balanceService->supportsExchange('kraken'));
        static::assertFalse($this->balanceService->supportsExchange('something_else'));
    }

    /**
     * @covers ::getBalances
     */
    public function testGetBalances(): void
    {
        $this->client
            ->expects(static::once())
            ->method('queryPrivate')
            ->with('Balance')
            ->willReturn(
                [
                    'BTC' => '3',
                    'EUR' => '2',
                ]
            )
        ;

        $result = $this->balanceService->getBalances();

        static::assertSame([
            ['BTC', '3 BTC', '3 BTC'],
            ['EUR', '2 EUR', '2 EUR'],
        ], $result);
    }
}

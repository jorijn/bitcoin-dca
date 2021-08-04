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

namespace Tests\Jorijn\Bitcoin\Dca\Service\Bitvavo;

use Jorijn\Bitcoin\Dca\Client\BitvavoClientInterface;
use Jorijn\Bitcoin\Dca\Service\Bitvavo\BitvavoBalanceService;
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
            ['BTC', '3 BTC', '1.00000000 BTC'],
            ['EUR', '2 EUR', '2.00000000 EUR'],
        ], $result);
    }

    protected function getStubResponse(): array
    {
        return [
            ['symbol' => 'BTC', 'available' => '3', 'inOrder' => '2'],
            ['symbol' => 'EUR', 'available' => '2', 'inOrder' => '0'],
        ];
    }
}

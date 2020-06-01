<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service;

use Jorijn\Bitcoin\Dca\Exception\NoExchangeAvailableException;
use Jorijn\Bitcoin\Dca\Service\BalanceService;
use Jorijn\Bitcoin\Dca\Service\BalanceServiceInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\BalanceService
 *
 * @internal
 */
final class BalanceServiceTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getBalances
     */
    public function testGetBalances(): void
    {
        $balances = [random_int(1000, 2000)];
        $exchange = 'configuredExchange'.random_int(1000, 2000);

        $unsupportedExchange = $this->createMock(BalanceServiceInterface::class);
        $supportedExchange = $this->createMock(BalanceServiceInterface::class);

        $unsupportedExchange->method('supportsExchange')->with($exchange)->willReturn(false);
        $supportedExchange->method('supportsExchange')->with($exchange)->willReturn(true);

        $unsupportedExchange->expects(static::never())->method('getBalances');
        $supportedExchange->expects(static::once())->method('getBalances')->willReturn($balances);

        $service = new BalanceService([$unsupportedExchange, $supportedExchange], $exchange);
        static::assertSame($balances, $service->getBalances());
    }

    /**
     * @covers ::__construct
     * @covers ::getBalances
     */
    public function getNoServicesAvailable(): void
    {
        $exchange = 'configuredExchange'.random_int(1000, 2000);

        $unsupportedExchange = $this->createMock(BalanceServiceInterface::class);
        $unsupportedExchange->method('supportsExchange')->with($exchange)->willReturn(false);
        $unsupportedExchange->expects(static::never())->method('getBalances');

        $this->expectException(NoExchangeAvailableException::class);

        $service = new BalanceService([$unsupportedExchange], $exchange);
        $service->getBalances();
    }
}

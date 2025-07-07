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

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Exception\KrakenClientException;
use Jorijn\Bitcoin\Dca\Service\Kraken\KrakenWithdrawService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Kraken\KrakenWithdrawService
 *
 * @covers ::__construct
 *
 * @internal
 */
final class KrakenWithdrawServiceTest extends TestCase
{
    private \Jorijn\Bitcoin\Dca\Client\KrakenClientInterface|\PHPUnit\Framework\MockObject\MockObject $client;

    private \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;
    private string $withdrawKey;
    private KrakenWithdrawService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(KrakenClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->withdrawKey = 'wk'.random_int(1000, 2000);

        $this->service = new KrakenWithdrawService($this->client, $this->logger, $this->withdrawKey);
    }

    /**
     * @covers ::withdraw
     */
    public function testWithdraw(): void
    {
        $balanceToWithdraw = random_int(100000, 900000);
        $addressToWithdrawTo = 'address'.random_int(1000, 2000);
        $reference = 'ref'.random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('queryPrivate')
            ->with(
                'Withdraw',
                [
                    'asset' => KrakenWithdrawService::ASSET_NAME,
                    'key' => $this->withdrawKey,
                    'amount' => bcdiv((string) $balanceToWithdraw, Bitcoin::SATOSHIS, Bitcoin::DECIMALS),
                ]
            )
            ->willReturn([
                'refid' => $reference,
            ])
        ;

        $withdraw = $this->service->withdraw($balanceToWithdraw, $addressToWithdrawTo);

        static::assertSame($balanceToWithdraw, $withdraw->getNetAmount());
        static::assertSame($addressToWithdrawTo, $withdraw->getRecipientAddress());
        static::assertSame($reference, $withdraw->getId());
    }

    /**
     * @covers ::getAvailableBalance
     */
    public function testGetAvailableBalanceInBitcoin(): void
    {
        $bitcoinBalance = random_int(1, 10) / 5;

        $this->client
            ->expects(static::once())
            ->method('queryPrivate')
            ->with('Balance')
            ->willReturn([
                KrakenWithdrawService::ASSET_NAME => (string) $bitcoinBalance,
                'EUR' => '2',
            ])
        ;

        static::assertSame(
            (int) bcmul((string) $bitcoinBalance, Bitcoin::SATOSHIS, 0),
            $this->service->getAvailableBalance()
        );
    }

    /**
     * @covers ::getAvailableBalance
     */
    public function testGetAvailableBalanceBitcoinNotAvailable(): void
    {
        $this->client
            ->expects(static::once())
            ->method('queryPrivate')
            ->with('Balance')
            ->willReturn([
                'EUR' => '2',
            ])
        ;

        static::assertSame(
            0,
            $this->service->getAvailableBalance()
        );
    }

    /**
     * @covers ::getAvailableBalance
     */
    public function testGetAvailableBalanceApiError(): void
    {
        $this->client
            ->expects(static::once())
            ->method('queryPrivate')
            ->with('Balance')
            ->willThrowException(new KrakenClientException())
        ;

        static::assertSame(
            0,
            $this->service->getAvailableBalance()
        );
    }

    /**
     * @covers ::supportsExchange
     */
    public function testSupportsExchange(): void
    {
        static::assertTrue($this->service->supportsExchange('kraken'));
        static::assertFalse($this->service->supportsExchange('other_exchange'));
    }

    /**
     * @covers ::getWithdrawFeeInSatoshis
     */
    public function testGetWithdrawFee(): void
    {
        $bitcoinBalance = random_int(1, 10) / 5;
        $fee = bcdiv((string) $feeInSatoshis = random_int(25000, 50000), Bitcoin::SATOSHIS, Bitcoin::DECIMALS);

        $this->client
            ->expects(static::exactly(2))
            ->method('queryPrivate')
            ->willReturnCallback(function (...$args) use ($bitcoinBalance, $fee) {
                static $count = 0;
                $count++;
                
                return match ($count) {
                    1 => (function () use ($args, $bitcoinBalance) {
                        [$method] = $args;
                        self::assertSame('Balance', $method);
                        return [KrakenWithdrawService::ASSET_NAME => (string) $bitcoinBalance];
                    })(),
                    2 => (function () use ($args, $fee) {
                        [$method] = $args;
                        self::assertSame('WithdrawInfo', $method);
                        return ['fee' => $fee];
                    })(),
                };
            })
        ;

        static::assertSame($feeInSatoshis, $this->service->getWithdrawFeeInSatoshis());
    }

    /**
     * @covers ::getWithdrawFeeInSatoshis
     */
    public function testGetWithdrawFeeApiError(): void
    {
        $krakenClientException = new KrakenClientException();

        $this->client
            ->expects(static::exactly(2))
            ->method('queryPrivate')
            ->willReturnCallback(function (...$args) use ($krakenClientException) {
                static $count = 0;
                $count++;
                
                return match ($count) {
                    1 => (function () use ($args) {
                        [$method] = $args;
                        self::assertSame('Balance', $method);
                        return ['BTC' => 3];
                    })(),
                    2 => (function () use ($args, $krakenClientException) {
                        [$method] = $args;
                        self::assertSame('WithdrawInfo', $method);
                        throw $krakenClientException;
                    })(),
                };
            })
        ;

        $this->expectExceptionObject($krakenClientException);

        $this->service->getWithdrawFeeInSatoshis();
    }
}

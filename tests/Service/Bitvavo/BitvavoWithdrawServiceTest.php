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

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Client\BitvavoClientInterface;
use Jorijn\Bitcoin\Dca\Service\Bitvavo\BitvavoWithdrawService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Bitvavo\BitvavoWithdrawService
 *
 * @covers ::__construct
 *
 * @internal
 */
final class BitvavoWithdrawServiceTest extends TestCase
{
    public const ADDRESS = 'address';
    public const API_CALL = 'apiCall';
    public const GENMKT_MONEY_INFO = 'GENMKT/money/info';

    private \Jorijn\Bitcoin\Dca\Client\BitvavoClientInterface|\PHPUnit\Framework\MockObject\MockObject $client;

    private \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;
    private BitvavoWithdrawService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(BitvavoClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new BitvavoWithdrawService(
            $this->client,
            $this->logger
        );
    }

    /**
     * @covers ::getAvailableBalance
     *
     * @throws \Exception
     */
    public function testGetBalance(): void
    {
        $this->client
            ->expects(static::exactly(2))
            ->method(self::API_CALL)
            ->with('balance', 'GET', [BitvavoWithdrawService::SYMBOL => 'BTC'])
            ->willReturnCallback(function () {
                static $count = 0;
                $count++;
                
                return match ($count) {
                    1 => [[BitvavoWithdrawService::SYMBOL => 'BTC', 'available' => '2.345', 'inOrder' => '1']],
                    2 => [],
                };
            })
        ;

        static::assertSame(134_500_000, $this->service->getAvailableBalance());
        static::assertSame(0, $this->service->getAvailableBalance());
    }

    /**
     * @covers ::withdraw
     *
     * @throws \Exception
     */
    public function testWithdraw(): void
    {
        $address = self::ADDRESS.random_int(1000, 2000);
        $amount = random_int(100000, 300000);
        $apiResponse = [];

        $bitvavoFee = random_int(1000, 2000);
        $netAmount = $amount - $bitvavoFee;
        $this->client
            ->expects(static::exactly(2))
            ->method(self::API_CALL)
            ->willReturnCallback(function (...$args) use ($netAmount, $address, $bitvavoFee, $apiResponse) {
                static $count = 0;
                $count++;
                
                return match ($count) {
                    1 => (function () use ($args, $bitvavoFee) {
                        [$endpoint, $method, $params] = $args;
                        self::assertSame('assets', $endpoint);
                        self::assertSame('GET', $method);
                        self::assertSame([BitvavoWithdrawService::SYMBOL => 'BTC'], $params);
                        return ['withdrawalFee' => bcdiv((string) $bitvavoFee, Bitcoin::SATOSHIS, Bitcoin::DECIMALS)];
                    })(),
                    2 => (function () use ($args, $netAmount, $address, $apiResponse) {
                        [$endpoint, $method, $params, $body] = $args;
                        self::assertSame('withdrawal', $endpoint);
                        self::assertSame('POST', $method);
                        self::assertSame([], $params);
                        self::assertArrayHasKey(BitvavoWithdrawService::SYMBOL, $body);
                        self::assertSame('BTC', $body[BitvavoWithdrawService::SYMBOL]);
                        self::assertArrayHasKey(self::ADDRESS, $body);
                        self::assertSame($address, $body[self::ADDRESS]);
                        self::assertArrayHasKey('amount', $body);
                        self::assertSame(
                            (string) bcdiv((string) $netAmount, Bitcoin::SATOSHIS, Bitcoin::DECIMALS),
                            $body['amount']
                        );
                        self::assertArrayHasKey('addWithdrawalFee', $body);
                        self::assertTrue($body['addWithdrawalFee']);
                        return $apiResponse;
                    })(),
                };
            })
        ;

        $completedWithdraw = $this->service->withdraw($amount, $address);

        static::assertSame($netAmount, $completedWithdraw->getNetAmount());
        static::assertSame($address, $completedWithdraw->getRecipientAddress());
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
     * @covers ::getWithdrawFeeInSatoshis
     */
    public function testFeeCalculation(): void
    {
        $bitvavoFee = random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method(self::API_CALL)
            ->with('assets', 'GET', [BitvavoWithdrawService::SYMBOL => 'BTC'])
            ->willReturn(['withdrawalFee' => bcdiv((string) $bitvavoFee, Bitcoin::SATOSHIS, Bitcoin::DECIMALS)])
        ;

        $withdrawFeeInSatoshis = $this->service->getWithdrawFeeInSatoshis();

        static::assertSame($bitvavoFee, $withdrawFeeInSatoshis);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Binance;

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Client\BinanceClientInterface;
use Jorijn\Bitcoin\Dca\Exception\BinanceClientException;
use Jorijn\Bitcoin\Dca\Service\Binance\BinanceWithdrawService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Binance\BinanceWithdrawService
 * @covers ::__construct
 *
 * @internal
 */
final class BinanceWithdrawServiceTest extends TestCase
{
    /** @var BinanceClientInterface|MockObject */
    protected $client;
    protected BinanceWithdrawService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(BinanceClientInterface::class);
        $this->service = new BinanceWithdrawService($this->client);
    }

    /**
     * @covers ::withdraw
     */
    public function testWithdraw(): void
    {
        $address = 'a'.random_int(1000, 2000);
        $amount = random_int(1000, 2000);
        $responseID = 'id'.random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'POST',
                'sapi/v1/capital/withdraw/apply',
                static::callback(function (array $options) use ($amount, $address) {
                    self::assertArrayHasKey('extra', $options);
                    self::assertArrayHasKey('security_type', $options['extra']);
                    self::assertSame('USER_DATA', $options['extra']['security_type']);
                    self::assertArrayHasKey('body', $options);
                    self::assertArrayHasKey('coin', $options['body']);
                    self::assertSame('BTC', $options['body']['coin']);
                    self::assertArrayHasKey('address', $options['body']);
                    self::assertSame($address, $options['body']['address']);
                    self::assertArrayHasKey('amount', $options['body']);
                    self::assertSame(
                        bcdiv((string) $amount, Bitcoin::SATOSHIS, Bitcoin::DECIMALS),
                        $options['body']['amount']
                    );

                    return true;
                })
            )
            ->willReturn(['id' => $responseID])
        ;

        $result = $this->service->withdraw($amount, $address);

        static::assertSame($result->getId(), $responseID);
        static::assertSame($result->getNetAmount(), $amount);
        static::assertSame($result->getRecipientAddress(), $address);
    }

    /**
     * @covers ::getAvailableBalance
     */
    public function testAvailableBalance(): void
    {
        $balance = '0.00123';

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'GET',
                'api/v3/account',
                static::callback(function (array $options) {
                    self::assertArrayHasKey('extra', $options);
                    self::assertArrayHasKey('security_type', $options['extra']);
                    self::assertSame('USER_DATA', $options['extra']['security_type']);

                    return true;
                })
            )
            ->willReturn(['balances' => [['asset' => 'BTC', 'free' => $balance]]])
        ;

        static::assertSame(123000, $this->service->getAvailableBalance());
    }

    /**
     * @covers ::getAvailableBalance
     */
    public function testAvailableBalanceReturnsZero(): void
    {
        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'GET',
                'api/v3/account',
            )
            ->willReturn(['balances' => [['asset' => 'ETH', 'free' => '5.000']]])
        ;

        static::assertSame(0, $this->service->getAvailableBalance());
    }

    /**
     * @covers ::getWithdrawFeeInSatoshis
     */
    public function testGetWithdrawalFee(): void
    {
        $fee = '0.0005';

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'GET',
                'sapi/v1/asset/assetDetail',
                static::callback(function (array $options) {
                    self::assertArrayHasKey('extra', $options);
                    self::assertArrayHasKey('security_type', $options['extra']);
                    self::assertSame('USER_DATA', $options['extra']['security_type']);

                    return true;
                })
            )
            ->willReturn(['BTC' => ['withdrawStatus' => true, 'withdrawFee' => $fee]])
        ;

        static::assertSame(50000, $this->service->getWithdrawFeeInSatoshis());
    }

    /**
     * @covers ::getWithdrawFeeInSatoshis
     */
    public function testGetWithdrawalFeeButBitcoinIsMissing(): void
    {
        $fee = '0.0005';

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'GET',
                'sapi/v1/asset/assetDetail',
            )
            ->willReturn(['ETH' => ['withdrawStatus' => true, 'withdrawFee' => $fee]])
        ;

        $this->expectExceptionMessage('BTC asset appears to be unknown on Binance');
        $this->expectException(BinanceClientException::class);

        $this->service->getWithdrawFeeInSatoshis();
    }

    /**
     * @covers ::getWithdrawFeeInSatoshis
     */
    public function testGetWithdrawalFeeButWithdrawalIsDisabled(): void
    {
        $fee = '0.0005';

        $this->client
            ->expects(static::once())
            ->method('request')
            ->with(
                'GET',
                'sapi/v1/asset/assetDetail',
            )
            ->willReturn(['BTC' => ['withdrawStatus' => false, 'withdrawFee' => $fee]])
        ;

        $this->expectExceptionMessage('withdrawal for BTC is disabled on Binance');
        $this->expectException(BinanceClientException::class);

        $this->service->getWithdrawFeeInSatoshis();
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

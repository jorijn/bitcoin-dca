<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Binance;

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Client\BinanceClientInterface;
use Jorijn\Bitcoin\Dca\Exception\BinanceClientException;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Service\WithdrawServiceInterface;

class BinanceWithdrawService implements WithdrawServiceInterface
{
    protected BinanceClientInterface $client;

    public function __construct(BinanceClientInterface $client)
    {
        $this->client = $client;
    }

    public function withdraw(int $balanceToWithdraw, string $addressToWithdrawTo): CompletedWithdraw
    {
        $response = $this->client->request('POST', 'sapi/v1/capital/withdraw/apply', [
            'extra' => ['security_type' => 'USER_DATA'],
            'body' => [
                'coin' => 'BTC',
                'address' => $addressToWithdrawTo,
                'amount' => bcdiv((string) $balanceToWithdraw, Bitcoin::SATOSHIS, Bitcoin::DECIMALS),
            ],
        ]);

        return new CompletedWithdraw($addressToWithdrawTo, $balanceToWithdraw, $response['id']);
    }

    public function getAvailableBalance(): int
    {
        $response = $this->client->request('GET', 'api/v3/account', [
            'extra' => ['security_type' => 'USER_DATA'],
        ]);

        if (isset($response['balances'])) {
            foreach ($response['balances'] as $balance) {
                if ('BTC' === $balance['asset']) {
                    return (int) bcmul($balance['free'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS);
                }
            }
        }

        return 0;
    }

    public function getWithdrawFeeInSatoshis(): int
    {
        $response = $this->client->request('GET', 'sapi/v1/asset/assetDetail', [
            'extra' => ['security_type' => 'USER_DATA'],
        ]);

        if (!isset($response['BTC'])) {
            throw new BinanceClientException('BTC asset appears to be unknown on Binance');
        }

        $assetDetails = $response['BTC'];

        if (false === $assetDetails['withdrawStatus'] ?? false) {
            throw new BinanceClientException('withdrawal for BTC is disabled on Binance');
        }

        return (int) bcmul((string) ($assetDetails['withdrawFee'] ?? 0), Bitcoin::SATOSHIS, Bitcoin::DECIMALS);
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'binance' === $exchange;
    }
}

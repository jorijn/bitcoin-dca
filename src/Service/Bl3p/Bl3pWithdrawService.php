<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Bl3p;

use Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Service\WithdrawServiceInterface;
use Psr\Log\LoggerInterface;

class Bl3pWithdrawService implements WithdrawServiceInterface
{
    public const BL3P = 'bl3p';

    protected Bl3pClientInterface $client;
    protected LoggerInterface $logger;

    public function __construct(Bl3pClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function withdraw(int $balanceToWithdraw, string $addressToWithdrawTo): CompletedWithdraw
    {
        $netAmountToWithdraw = $balanceToWithdraw;
        $response = $this->client->apiCall('GENMKT/money/withdraw', [
            'currency' => 'BTC',
            'address' => $addressToWithdrawTo,
            'amount_int' => $netAmountToWithdraw,
        ]);

        return new CompletedWithdraw($addressToWithdrawTo, $netAmountToWithdraw, $response['data']['id']);
    }

    public function getAvailableBalance(): int
    {
        $response = $this->client->apiCall('GENMKT/money/info');

        return (int) ($response['data']['wallets']['BTC']['available']['value_int'] ?? 0);
    }

    public function getWithdrawFeeInSatoshis(): int
    {
        return 30000;
    }

    public function supportsExchange(string $exchange): bool
    {
        return self::BL3P === $exchange;
    }
}

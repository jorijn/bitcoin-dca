<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Exception\KrakenClientException;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Service\WithdrawServiceInterface;
use Psr\Log\LoggerInterface;

class KrakenWithdrawService implements WithdrawServiceInterface
{
    public const ASSET_NAME = 'XXBT';

    protected KrakenClientInterface $client;
    protected LoggerInterface $logger;
    protected ?string $withdrawKey;

    public function __construct(KrakenClientInterface $client, LoggerInterface $logger, ?string $withdrawKey)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->withdrawKey = $withdrawKey;
    }

    public function withdraw(int $balanceToWithdraw, string $addressToWithdrawTo): CompletedWithdraw
    {
        $response = $this->client->queryPrivate('Withdraw', [
            'asset' => self::ASSET_NAME,
            'key' => $this->withdrawKey,
            'amount' => bcdiv((string) $balanceToWithdraw, Bitcoin::SATOSHIS, Bitcoin::DECIMALS),
        ]);

        return new CompletedWithdraw($addressToWithdrawTo, $balanceToWithdraw, $response['refid']);
    }

    public function getAvailableBalance(): int
    {
        try {
            $response = $this->client->queryPrivate('Balance');

            foreach ($response as $symbol => $available) {
                if (self::ASSET_NAME === $symbol) {
                    return (int) bcmul((string) $available, Bitcoin::SATOSHIS, Bitcoin::DECIMALS);
                }
            }
        } catch (KrakenClientException $exception) {
            return 0;
        }

        return 0;
    }

    public function getWithdrawFeeInSatoshis(): int
    {
        $response = $this->client->queryPrivate(
            'WithdrawInfo',
            [
                'asset' => self::ASSET_NAME,
                'key' => $this->withdrawKey,
                'amount' => bcdiv((string) $this->getAvailableBalance(), Bitcoin::SATOSHIS, Bitcoin::DECIMALS),
            ]
        );

        return (int) bcmul((string) $response['fee'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS);
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'kraken' === $exchange;
    }
}

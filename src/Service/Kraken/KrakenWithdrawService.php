<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Exception\KrakenClientException;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Service\WithdrawServiceInterface;
use Psr\Log\LoggerInterface;

class KrakenWithdrawService implements WithdrawServiceInterface
{
    public const ASSET_NAME = 'XXBT';
    private const DIVISOR = '100000000';

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
            'amount' => bcdiv((string) $balanceToWithdraw, self::DIVISOR, 8),
        ]);

        return new CompletedWithdraw($addressToWithdrawTo, $balanceToWithdraw, $response['refid']);
    }

    public function getAvailableBalance(): int
    {
        try {
            $response = $this->client->queryPrivate('Balance');

            foreach ($response as $symbol => $available) {
                if (self::ASSET_NAME === $symbol) {
                    return (int) bcmul((string) $available, self::DIVISOR, 8);
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
                'amount' => bcdiv((string) $this->getAvailableBalance(), self::DIVISOR, 8),
            ]
        );

        return (int) bcmul((string) $response['fee'], self::DIVISOR, 8);
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'kraken' === $exchange;
    }
}

<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Service\BalanceServiceInterface;

class KrakenBalanceService implements BalanceServiceInterface
{
    protected KrakenClientInterface $client;

    public function __construct(KrakenClientInterface $client)
    {
        $this->client = $client;
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'kraken' === $exchange;
    }

    public function getBalances(): array
    {
        $response = $this->client->queryPrivate('Balance');
        $rows = [];

        foreach ($response as $symbol => $available) {
            $rows[] = [$symbol, $available.' '.$symbol, $available.' '.$symbol];
        }

        return $rows;
    }
}

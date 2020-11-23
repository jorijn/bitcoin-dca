<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Service\WithdrawServiceInterface;
use Psr\Log\LoggerInterface;

class KrakenWithdrawService implements WithdrawServiceInterface
{
    protected KrakenClientInterface $client;
    protected LoggerInterface $logger;

    public function __construct(KrakenClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function withdraw(int $balanceToWithdraw, string $addressToWithdrawTo): CompletedWithdraw
    {
        // TODO
    }

    public function getAvailableBalance(): int
    {
        // TODO
    }

    public function getWithdrawFeeInSatoshis(): int
    {
        // TODO
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'kraken' === $exchange;
    }
}

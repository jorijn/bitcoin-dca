<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Bitvavo;

use Jorijn\Bitcoin\Dca\Client\BitvavoClientInterface;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Service\WithdrawServiceInterface;
use Psr\Log\LoggerInterface;

class BitvavoWithdrawService implements WithdrawServiceInterface
{
    public const SYMBOL = 'symbol';
    protected BitvavoClientInterface $client;
    protected LoggerInterface $logger;

    public function __construct(BitvavoClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function withdraw(int $balanceToWithdraw, string $addressToWithdrawTo): CompletedWithdraw
    {
        $netAmountToWithdraw = $balanceToWithdraw - $this->getWithdrawFeeInSatoshis();

        $this->client->apiCall('withdrawal', 'POST', [], [
            self::SYMBOL => 'BTC',
            'address' => $addressToWithdrawTo,
            'amount' => (string) ($netAmountToWithdraw / 100000000),
            'addWithdrawalFee' => true,
        ]);

        // bitvavo doesn't support any ID for withdrawal, using timestamp instead
        return new CompletedWithdraw($addressToWithdrawTo, $netAmountToWithdraw, (string) time());
    }

    public function getAvailableBalance(): int
    {
        $response = $this->client->apiCall('balance', 'GET', [self::SYMBOL => 'BTC']);

        if (!isset($response[0]) || 'BTC' !== $response[0][self::SYMBOL]) {
            return 0;
        }

        $available = (int) ($response[0]['available'] * 100000000);
        $inOrder = (int) ($response[0]['inOrder'] * 100000000);

        return $available - $inOrder;
    }

    public function getWithdrawFeeInSatoshis(): int
    {
        $response = $this->client->apiCall('assets', 'GET', [self::SYMBOL => 'BTC']);

        return (int) ($response['withdrawalFee'] * 100000000);
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'bitvavo' === $exchange;
    }
}

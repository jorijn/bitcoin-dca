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
    private const DIVISOR = '100000000';
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
            'amount' => bcdiv((string) $netAmountToWithdraw, self::DIVISOR, 8),
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

        $available = (int) bcmul($response[0]['available'], self::DIVISOR, 8);
        $inOrder = (int) bcmul($response[0]['inOrder'], self::DIVISOR, 8);

        return $available - $inOrder;
    }

    public function getWithdrawFeeInSatoshis(): int
    {
        $response = $this->client->apiCall('assets', 'GET', [self::SYMBOL => 'BTC']);

        return (int) bcmul($response['withdrawalFee'], self::DIVISOR, 8);
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'bitvavo' === $exchange;
    }
}

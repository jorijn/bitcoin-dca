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

namespace Jorijn\Bitcoin\Dca\Service\Bitvavo;

use Jorijn\Bitcoin\Dca\Bitcoin;
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
            'amount' => bcdiv((string) $netAmountToWithdraw, Bitcoin::SATOSHIS, Bitcoin::DECIMALS),
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

        $available = (int) bcmul($response[0]['available'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS);
        $inOrder = (int) bcmul($response[0]['inOrder'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS);

        return $available - $inOrder;
    }

    public function getWithdrawFeeInSatoshis(): int
    {
        $response = $this->client->apiCall('assets', 'GET', [self::SYMBOL => 'BTC']);

        return (int) bcmul($response['withdrawalFee'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS);
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'bitvavo' === $exchange;
    }
}

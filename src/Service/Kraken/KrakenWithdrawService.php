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

namespace Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Exception\KrakenClientException;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Service\WithdrawServiceInterface;
use Psr\Log\LoggerInterface;

class KrakenWithdrawService implements WithdrawServiceInterface
{
    final public const ASSET_NAME = 'XXBT';

    public function __construct(
        protected KrakenClientInterface $krakenClient,
        protected LoggerInterface $logger,
        protected ?string $withdrawKey
    ) {
    }

    public function withdraw(int $balanceToWithdraw, string $addressToWithdrawTo): CompletedWithdraw
    {
        $response = $this->krakenClient->queryPrivate('Withdraw', [
            'asset' => self::ASSET_NAME,
            'key' => $this->withdrawKey,
            'amount' => bcdiv((string) $balanceToWithdraw, Bitcoin::SATOSHIS, Bitcoin::DECIMALS),
        ]);

        return new CompletedWithdraw($addressToWithdrawTo, $balanceToWithdraw, $response['refid']);
    }

    public function getAvailableBalance(): int
    {
        try {
            $response = $this->krakenClient->queryPrivate('Balance');

            foreach ($response as $symbol => $available) {
                if (self::ASSET_NAME === $symbol) {
                    return (int) bcmul((string) $available, Bitcoin::SATOSHIS, Bitcoin::DECIMALS);
                }
            }
        } catch (KrakenClientException) {
            return 0;
        }

        return 0;
    }

    public function getWithdrawFeeInSatoshis(): int
    {
        $response = $this->krakenClient->queryPrivate(
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

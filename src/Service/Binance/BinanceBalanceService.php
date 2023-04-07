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

namespace Jorijn\Bitcoin\Dca\Service\Binance;

use Jorijn\Bitcoin\Dca\Client\BinanceClientInterface;
use Jorijn\Bitcoin\Dca\Service\BalanceServiceInterface;

class BinanceBalanceService implements BalanceServiceInterface
{
    public function __construct(protected BinanceClientInterface $binanceClient)
    {
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'binance' === $exchange;
    }

    public function getBalances(): array
    {
        $response = $this->binanceClient->request('GET', 'api/v3/account', [
            'extra' => ['security_type' => 'USER_DATA'],
        ]);

        return array_filter(
            array_reduce($response['balances'], static function (array $balances, array $asset): array {
                $decimals = \strlen(explode('.', (string) $asset['free'])[1]);

                // binance holds a gazillion altcoins, no interest in showing hundreds if their balance
                // is zero.
                if (bccomp((string) $asset['free'], '0', $decimals) <= 0) {
                    $balances[$asset['asset']] = false;

                    return $balances;
                }

                $balances[$asset['asset']] = [
                    $asset['asset'],
                    bcadd((string) $asset['free'], (string) $asset['locked'], $decimals),
                    $asset['free'],
                ];

                return $balances;
            }, [])
        );
    }
}

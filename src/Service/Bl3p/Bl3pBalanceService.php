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

namespace Jorijn\Bitcoin\Dca\Service\Bl3p;

use Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface;
use Jorijn\Bitcoin\Dca\Service\BalanceServiceInterface;

class Bl3pBalanceService implements BalanceServiceInterface
{
    public function __construct(protected Bl3pClientInterface $bl3pClient)
    {
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'bl3p' === $exchange;
    }

    public function getBalances(): array
    {
        $response = $this->bl3pClient->apiCall('GENMKT/money/info');
        $rows = [];

        foreach ($response['data']['wallets'] ?? [] as $currency => $wallet) {
            if (0 === (int) $wallet['balance']['value_int']) {
                continue;
            }

            $rows[] = [$currency, $wallet['balance']['display'], $wallet['available']['display']];
        }

        return $rows;
    }
}

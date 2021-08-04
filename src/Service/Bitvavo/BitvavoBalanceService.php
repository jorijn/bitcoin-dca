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

use Jorijn\Bitcoin\Dca\Client\BitvavoClientInterface;
use Jorijn\Bitcoin\Dca\Service\BalanceServiceInterface;

class BitvavoBalanceService implements BalanceServiceInterface
{
    protected BitvavoClientInterface $client;

    public function __construct(BitvavoClientInterface $client)
    {
        $this->client = $client;
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'bitvavo' === $exchange;
    }

    public function getBalances(): array
    {
        $response = $this->client->apiCall('balance');
        $rows = [];

        foreach ($response as ['symbol' => $symbol, 'available' => $available, 'inOrder' => $inOrder]) {
            $rows[] = [$symbol, $available.' '.$symbol, bcsub($available, $inOrder, 8).' '.$symbol];
        }

        return $rows;
    }
}

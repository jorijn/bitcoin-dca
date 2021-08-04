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

namespace Jorijn\Bitcoin\Dca\Client;

interface KrakenClientInterface
{
    public function queryPublic(string $method, array $arguments = []): array;

    public function queryPrivate(string $method, array $arguments = []): array;
}

<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Client;

interface BinanceClientInterface
{
    public function request(string $method, string $url, array $options = []): array;
}

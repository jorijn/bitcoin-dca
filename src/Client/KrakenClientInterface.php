<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Client;

interface KrakenClientInterface
{
    public function queryPublic(string $method, array $arguments = []): array;

    public function queryPrivate(string $method, array $arguments = []): array;
}

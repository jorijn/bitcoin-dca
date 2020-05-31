<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Client;

interface BitvavoClientInterface
{
    public function apiCall(string $path, string $method = 'GET', array $parameters = [], array $body = []): array;
}

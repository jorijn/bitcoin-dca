<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Factory;

use Jorijn\Bl3pDca\Client\Bl3pClient;
use Jorijn\Bl3pDca\Client\Bl3pClientInterface;

class Bl3pClientFactory
{
    protected string $apiUrl;
    protected string $publicKey;
    protected string $privateKey;

    public function __construct(string $apiUrl, string $publicKey, string $privateKey)
    {
        $this->apiUrl = $apiUrl;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    public function createApi(): Bl3pClientInterface
    {
        return new Bl3pClient($this->apiUrl, $this->publicKey, $this->privateKey);
    }
}

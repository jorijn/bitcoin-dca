<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Factory;

use Jorijn\Bl3pDca\Client\Bl3PClient;
use Jorijn\Bl3pDca\Client\Bl3PClientInterface;

class Bl3PClientFactory
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

    public function createApi(): Bl3PClientInterface
    {
        return new Bl3PClient($this->apiUrl, $this->publicKey, $this->privateKey);
    }
}

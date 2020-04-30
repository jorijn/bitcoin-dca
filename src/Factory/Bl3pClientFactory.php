<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Factory;

use Jorijn\Bl3pDca\Client\Bl3pClient;
use Jorijn\Bl3pDca\Client\Bl3pClientInterface;
use Psr\Log\LoggerInterface;

class Bl3pClientFactory
{
    protected string $apiUrl;
    protected string $publicKey;
    protected string $privateKey;
    protected LoggerInterface $logger;

    public function __construct(string $apiUrl, string $publicKey, string $privateKey, LoggerInterface $logger)
    {
        $this->apiUrl = $apiUrl;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->logger = $logger;
    }

    public function createApi(): Bl3pClientInterface
    {
        return new Bl3pClient($this->apiUrl, $this->publicKey, $this->privateKey, $this->logger);
    }
}

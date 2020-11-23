<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Client;

use Jorijn\Bitcoin\Dca\Exception\KrakenClientException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class KrakenClient implements KrakenClientInterface
{
    public const USER_AGENT = 'Mozilla/4.0 (compatible; Kraken PHP client; Jorijn/BitcoinDca; '.PHP_OS.'; PHP/'.PHP_VERSION.')';

    protected HttpClientInterface $httpClient;
    protected LoggerInterface $logger;
    protected string $publicKey;
    protected string $privateKey;
    protected string $version;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $publicKey,
        string $privateKey,
        string $version = '0'
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->version = $version;
    }

    public function queryPublic(string $method, array $arguments = []): array
    {
        $serverResponse = $this->httpClient->request('POST', sprintf('%s/public/%s', $this->version, $method), [
            'body' => $arguments,
            'headers' => [
                'User-Agent' => self::USER_AGENT,
            ],
        ]);

        return $this->validateResponse($serverResponse->toArray());
    }

    public function queryPrivate(string $method, array $arguments = []): array
    {
        $nonce = explode(' ', microtime());
        $arguments['nonce'] = $nonce[1].str_pad(substr($nonce[0], 2, 6), 6, '0');

        $path = sprintf('/%s/private/%s', $this->version, $method);
        $sign = hash_hmac(
            'sha512',
            $path.hash('sha256', $arguments['nonce'].http_build_query($arguments, '', '&'), true),
            base64_decode($this->privateKey, true),
            true
        );

        $headers = [
            'API-Key' => $this->publicKey,
            'API-Sign' => base64_encode($sign),
            'User-Agent' => self::USER_AGENT,
        ];

        $serverResponse = $this->httpClient->request('POST', $path, [
            'body' => $arguments,
            'headers' => $headers,
        ]);

        return $this->validateResponse($serverResponse->toArray());
    }

    protected function validateResponse(array $response): array
    {
        if (isset($response['error']) && !empty($response['error'])) {
            throw new KrakenClientException(implode(', ', $response['error']));
        }

        return $response['result'] ?? $response;
    }
}

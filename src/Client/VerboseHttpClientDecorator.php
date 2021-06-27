<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Client;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

class VerboseHttpClientDecorator implements HttpClientInterface
{
    use LoggerAwareTrait;

    protected HttpClientInterface $httpClient;
    protected bool $enabled;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, bool $enabled = false)
    {
        $this->httpClient = $httpClient;
        $this->enabled = $enabled;

        $this->setLogger($logger);
    }

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->httpClient->stream($responses, $timeout);
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (!$this->enabled) {
            return $this->httpClient->request($method, $url, $options);
        }

        $this->logger->debug(
            '[API call] about to make a request',
            [
                'method' => $method,
                'url' => $url,
                'options' => $options,
            ]
        );

        try {
            $response = $this->httpClient->request($method, $url, $options);
        } catch (\Throwable $exception) {
            $this->logger->debug(
                '[API call] exception was raised',
                [
                    'reason' => $exception->getMessage() ?: get_class($exception),
                    'exception' => $exception,
                ]
            );

            throw $exception;
        }

        $this->logger->debug(
            '[API call] received a response from API remote party',
            [
                'method' => $method,
                'url' => $url,
                'options' => $options,
                'response' => $response->getContent(false),
                'http_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(false),
                'info' => $response->getInfo(),
            ]
        );

        return $response;
    }
}

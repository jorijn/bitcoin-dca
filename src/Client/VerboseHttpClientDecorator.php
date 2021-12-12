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

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Throwable;

class VerboseHttpClientDecorator implements HttpClientInterface
{
    use LoggerAwareTrait;
    use DecoratorTrait;

    public function __construct(
        protected HttpClientInterface $httpClient,
        LoggerInterface $logger,
        protected bool $enabled = false
    ) {
        $this->setLogger($logger);
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
        } catch (Throwable $exception) {
            $this->logger->debug(
                '[API call] exception was raised',
                [
                    'reason' => $exception->getMessage() ?: $exception::class,
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

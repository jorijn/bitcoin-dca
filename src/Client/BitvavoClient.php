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

use Jorijn\Bitcoin\Dca\Exception\BitvavoClientException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BitvavoClient implements BitvavoClientInterface
{
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected LoggerInterface $logger,
        protected ?string $apiKey,
        protected ?string $apiSecret,
        protected ?int $operatorId = null,
        protected string $accessWindow = '10000'
    ) {
    }

    public function apiCall(
        string $path,
        string $method = 'GET',
        array $parameters = [],
        array $body = [],
        string $now = null
    ): array {
        if (null === $now) {
            $time = explode(' ', microtime());
            $now = $time[1].substr($time[0], 2, 3);
        }
        
        // add the required operatorId to the body
        $body['operatorId'] = $this->operatorId;

        $query = http_build_query($parameters, '', '&');
        $endpointParams = $path.(empty($parameters) ? null : '?'.$query);
        $hashString = $now.$method.'/v2/'.$endpointParams;

        if (!empty($body)) {
            $hashString .= json_encode($body, JSON_THROW_ON_ERROR);
        }

        $headers = [
            'Bitvavo-Access-Key' => $this->apiKey,
            'Bitvavo-Access-Signature' => hash_hmac('sha256', $hashString, $this->apiSecret),
            'Bitvavo-Access-Timestamp' => $now,
            'Bitvavo-Access-Window' => $this->accessWindow,
            'User-Agent' => 'Mozilla/4.0 (compatible; Bitvavo PHP client; Jorijn/BitcoinDca; '.PHP_OS.'; PHP/'.PHP_VERSION.')',
            'Content-Type' => 'application/json',
        ];

        $serverResponse = $this->httpClient->request(
            $method,
            $path,
            [
                'headers' => $headers,
                'query' => $parameters,
            ] + (empty($body) ? [] : ['json' => $body])
        );

        $responseData = $serverResponse->toArray(false);

        if (isset($responseData['errorCode'])) {
            throw new BitvavoClientException($responseData['error'], $responseData['errorCode']);
        }

        return $responseData;
    }
}

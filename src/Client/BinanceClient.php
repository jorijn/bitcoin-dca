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

use InvalidArgumentException;
use Jorijn\Bitcoin\Dca\Exception\BinanceClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BinanceClient implements BinanceClientInterface
{
    /** @var string */
    public const USER_AGENT = 'Mozilla/4.0 (compatible; Binance PHP client; Jorijn/BitcoinDca; '.PHP_OS.'; PHP/'.PHP_VERSION.')';

    /** @var string */
    public const HASH_ALGO = 'sha256';

    public function __construct(
        protected HttpClientInterface $httpClient,
        protected ?string $apiKey,
        protected ?string $apiSecret
    ) {
    }

    /**
     * This decorator implementation on the HttpClientInterface will check the given options for
     * a specific type of security key, depending on which — the decorator will sign the request
     * and add the API key to the header array.
     *
     * @noinspection PhpMissingBreakStatementInspection
     */
    public function request(string $method, string $url, array $options = []): array
    {
        $extra = $options['extra'] ?? [];

        if (!isset($options['headers']) || !\is_array($options['headers'])) {
            $options['headers'] = [];
        }

        $options['headers']['User-Agent'] = self::USER_AGENT;

        switch ($extra['security_type'] ?? null) {
            case 'TRADE':
            case 'USER_DATA':
                [$method, $url, $options] = $this->addSignatureToRequest($method, $url, $options);
            // no break
            case 'USER_STREAM':
            case 'MARKET_DATA':
                // @noinspection SuspiciousAssignmentsInspection
                [$method, $url, $options] = $this->addApiKeyToRequest($method, $url, $options);
            // no break
            case 'NONE':
            default:
                return $this->parse($this->httpClient->request($method, $url, $options));
        }
    }

    protected function addSignatureToRequest(string $method, string $url, array $options): array
    {
        // fetch the query and add the required timestamp
        $parameters = $options['query'] ?? [];

        // check and validate any present body
        if (isset($options['body'])) {
            if (!\is_array($options['body'])) {
                throw new InvalidArgumentException(
                    'passing any other request body than type `array` on '.__CLASS__.' is not supported'
                );
            }

            $parameters = array_merge($parameters, $options['body']);
        }

        // clear up the request as we are overwriting
        unset($options['query'], $options['body']);

        // add the timestamp so the exchange can invalidate when there is too much network lag
        $parameters['timestamp'] = number_format(microtime(true) * 1000, 0, '.', '');

        // build the query string and hash it into a signature
        $parameterString = http_build_query($parameters, '', '&');
        $signature = hash_hmac(self::HASH_ALGO, $parameterString, $this->apiSecret);

        // if the request was designed to be a POST request, take all the options and move them to the body --
        // only signature is allowed as query string
        if ('POST' === $method) {
            $options['body'] = array_merge($parameters, ['signature' => $signature]);
        } else {
            $options['query'] = array_merge($parameters, ['signature' => $signature]);
        }

        return [$method, $url, $options];
    }

    protected function addApiKeyToRequest(string $method, string $url, array $options): array
    {
        $options['headers'] = array_merge($options['headers'] ?? [], ['X-MBX-APIKEY' => $this->apiKey]);

        return [$method, $url, $options];
    }

    /**
     * This method should translate the response into a usable array object.
     */
    protected function parse(ResponseInterface $response): array
    {
        $result = $response->toArray(false);

        if (isset($result['code'], $result['msg'])) {
            throw new BinanceClientException($result['msg'], $result['code']);
        }

        return $result;
    }
}

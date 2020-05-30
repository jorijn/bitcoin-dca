<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Client;

use Jorijn\Bitcoin\Dca\Exception\Bl3pClientException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @source https://github.com/BitonicNL/bl3p-api/blob/master/examples/php/example.php
 */
class Bl3pClient implements Bl3pClientInterface
{
    public const LOG_API_CALL_FAILED = 'API call failed: {url}';
    public const API_KEY_RESULT = 'result';
    public const API_KEY_DATA = 'data';
    public const LOG_CONTEXT_PARAMETERS = 'parameters';
    public const LOG_CONTEXT_URL = 'url';
    public const API_KEY_MESSAGE = 'message';

    protected HttpClientInterface $httpClient;
    protected LoggerInterface $logger;
    protected string $publicKey;
    protected string $privateKey;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $publicKey,
        string $privateKey
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * {@inheritdoc}
     */
    public function apiCall($path, $parameters = []): array
    {
        // generate a nonce as micro time, with as-string handling to avoid problems with 32bits systems
        $microtime = explode(' ', microtime());
        $parameters['nonce'] = $microtime[1].substr($microtime[0], 2, 6);

        // generate the POST data string
        $post_data = http_build_query($parameters, '', '&');
        $body = $path.\chr(0).$post_data;

        // build signature for Rest-Sign
        $signature = base64_encode(hash_hmac('sha512', $body, base64_decode($this->privateKey, true), true));

        // set headers
        $headers = [
            'Rest-Key' => $this->publicKey,
            'Rest-Sign' => $signature,
            'User-Agent' => 'Mozilla/4.0 (compatible; BL3P PHP client; Jorijn/Bl3pDca; '.PHP_OS.'; PHP/'.PHP_VERSION.')',
        ];

        $serverResponse = $this->httpClient->request('POST', $path, [
            'headers' => $headers,
            'body' => $parameters,
        ]);

        try {
            // convert json into an array
            $result = $serverResponse->toArray(true);
        } catch (\Throwable $exception) {
            $this->logger->error(
                self::LOG_API_CALL_FAILED,
                [self::LOG_CONTEXT_URL => $path, self::LOG_CONTEXT_PARAMETERS => $parameters]
            );

            throw new Bl3pClientException(
                'API request failed: '.$exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        if (!\array_key_exists(self::API_KEY_RESULT, $result)) {
            // note that data now is the first element in the array.
            $result[self::API_KEY_DATA] = $result;
            $result[self::API_KEY_RESULT] = 'success';

            // remove all the keys in $result except 'result'  and 'data'
            return array_intersect_key($result, array_flip([self::API_KEY_RESULT, self::API_KEY_DATA]));
        }

        // check returned result of call, if not success then throw an exception with additional information
        if ('success' !== $result[self::API_KEY_RESULT]) {
            if (!isset($result[self::API_KEY_DATA]['code'], $result[self::API_KEY_DATA][self::API_KEY_MESSAGE])) {
                $this->logger->error(self::LOG_API_CALL_FAILED, [
                    self::LOG_CONTEXT_URL => $path,
                    self::LOG_CONTEXT_PARAMETERS => $parameters,
                    'response' => var_export($result[self::API_KEY_DATA], true),
                ]);

                throw new Bl3pClientException(sprintf(
                    'Received unsuccessful state, and additionally a malformed response: %s',
                    var_export($result[self::API_KEY_DATA], true)
                ));
            }

            $this->logger->error(self::LOG_API_CALL_FAILED, [
                self::LOG_CONTEXT_URL => $path,
                self::LOG_CONTEXT_PARAMETERS => $parameters,
                'code' => $result[self::API_KEY_DATA]['code'],
                self::API_KEY_MESSAGE => $result[self::API_KEY_DATA][self::API_KEY_MESSAGE],
            ]);

            throw new Bl3pClientException(sprintf(
                'API request unsuccessful: [%s] %s',
                $result[self::API_KEY_DATA]['code'],
                $result[self::API_KEY_DATA][self::API_KEY_MESSAGE]
            ));
        }

        $this->logger->info(
            'API call success: {url}',
            [self::LOG_CONTEXT_URL => $path, self::LOG_CONTEXT_PARAMETERS => $parameters]
        );

        return $result;
    }
}

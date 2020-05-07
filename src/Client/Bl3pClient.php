<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Client;

use Jorijn\Bl3pDca\Exception\Bl3pClientException;
use Psr\Log\LoggerInterface;

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

    protected LoggerInterface $logger;
    protected string $publicKey;
    protected string $privateKey;
    protected string $url;

    /**
     * Set the url to call, the public key and the private key.
     *
     * @param string $url        Url to call (https://api.bl3p.eu)
     * @param string $publicKey  Your Public API key
     * @param string $privateKey Your Private API key
     */
    public function __construct(string $url, string $publicKey, string $privateKey, LoggerInterface $logger)
    {
        $this->url = $url;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function apiCall($path, $params = []): array
    {
        // generate a nonce as microtime, with as-string handling to avoid problems with 32bits systems
        $mt = explode(' ', microtime());
        $params['nonce'] = $mt[1].substr($mt[0], 2, 6);

        // generate the POST data string
        $post_data = http_build_query($params, '', '&');
        $body = $path.\chr(0).$post_data;

        // build signature for Rest-Sign
        $sign = base64_encode(hash_hmac('sha512', $body, base64_decode($this->privateKey, true), true));

        // combine the url and the desired path
        $fullPath = $this->url.$path;

        // set headers
        $headers = [
            'Rest-Key: '.$this->publicKey,
            'Rest-Sign: '.$sign,
        ];

        // build curl call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Mozilla/4.0 (compatible; BL3P PHP client; Jorijn/Bl3pDca; '.PHP_OS.'; PHP/'.PHP_VERSION.')'
        );
        curl_setopt($ch, CURLOPT_URL, $fullPath);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // execute curl request
        $res = curl_exec($ch);

        // throw exception with additional information when curl request returns false
        if (false === $res) {
            throw new Bl3pClientException('API request failed: Could not get reply from API: '.curl_error($ch));
        }

        // close curl connection
        curl_close($ch);

        // convert json into an array
        $result = json_decode($res, true, 512, JSON_THROW_ON_ERROR);

        // check json convert result and throw an exception if invalid
        if (!$result) {
            $this->logger->error(
                self::LOG_API_CALL_FAILED,
                [self::LOG_CONTEXT_URL => $fullPath, self::LOG_CONTEXT_PARAMETERS => $params]
            );

            throw new Bl3pClientException('API request failed: Invalid JSON-data received: '.substr($res, 0, 100));
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
                    self::LOG_CONTEXT_URL => $fullPath,
                    self::LOG_CONTEXT_PARAMETERS => $params,
                    'response' => var_export($result[self::API_KEY_DATA], true),
                ]);

                throw new Bl3pClientException(sprintf(
                    'Received unsuccessful state, and additionally a malformed response: %s',
                    var_export($result[self::API_KEY_DATA], true)
                ));
            }

            $this->logger->error(self::LOG_API_CALL_FAILED, [
                self::LOG_CONTEXT_URL => $fullPath,
                self::LOG_CONTEXT_PARAMETERS => $params,
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
            [self::LOG_CONTEXT_URL => $fullPath, self::LOG_CONTEXT_PARAMETERS => $params]
        );

        return $result;
    }
}

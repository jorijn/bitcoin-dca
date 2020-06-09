<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Client;

use Jorijn\Bitcoin\Dca\Client\BitvavoClient;
use Jorijn\Bitcoin\Dca\Exception\BitvavoClientException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Client\BitvavoClient
 * @covers ::__construct
 *
 * @internal
 */
final class BitvavoClientTest extends TestCase
{
    private const HEADERS = 'headers';

    /** @var HttpClientInterface|MockObject */
    private $httpClient;
    private string $accessWindow;
    private string $apiKey;
    private string $apiSecret;
    /** @var LoggerInterface|MockObject */
    private $logger;
    private BitvavoClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->apiKey = 'apikey'.random_int(1000, 2000);
        $this->apiSecret = 'apisecret'.random_int(1000, 2000);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->accessWindow = (string) random_int(1000, 2000);

        $this->client = new BitvavoClient(
            $this->httpClient,
            $this->logger,
            $this->apiKey,
            $this->apiSecret,
            $this->accessWindow
        );
    }

    public function differentApiCalls(): array
    {
        return [
            'GET /?foo=bar' => ['GET', ['foo' => 'bar'], []],
            'POST / {"foo":"bar"}' => ['POST', [], ['foo' => 'bar']],
            'POST /?foo=bar {"foo":"bar"}' => ['POST', ['foo' => 'bar'], ['foo' => 'bar']],
            'DELETE / {"foo":"bar"}' => ['DELETE', [], ['foo' => 'bar']],
        ];
    }

    /**
     * @covers ::apiCall
     * @dataProvider differentApiCalls
     */
    public function testApiCall(string $method, array $parameters, array $body): void
    {
        $path = 'path'.random_int(1000, 2000);
        $now = time() * 1000;
        $returnData = ['return' => random_int(1000, 2000)];

        $query = http_build_query($parameters, '', '&');
        $endpointParams = $path.(!empty($parameters) ? '?'.$query : null);
        $hashString = $now.$method.'/v2/'.$endpointParams;

        if (!empty($body)) {
            $hashString .= json_encode($body, JSON_THROW_ON_ERROR);
        }

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects(static::once())
            ->method('toArray')
            ->willReturn($returnData)
        ;

        $this->httpClient
            ->expects(static::once())
            ->method('request')
            ->with(
                $method,
                $path,
                static::callback(
                    function (array $options) use ($parameters, $now, $hashString, $body) {
                        self::assertArrayHasKey(self::HEADERS, $options);
                        self::assertArrayHasKey('Bitvavo-Access-Key', $options[self::HEADERS]);
                        self::assertSame($this->apiKey, $options[self::HEADERS]['Bitvavo-Access-Key']);
                        self::assertArrayHasKey('Bitvavo-Access-Signature', $options[self::HEADERS]);
                        self::assertSame(
                            hash_hmac('sha256', $hashString, $this->apiSecret),
                            $options[self::HEADERS]['Bitvavo-Access-Signature']
                        );
                        self::assertArrayHasKey('Bitvavo-Access-Timestamp', $options[self::HEADERS]);
                        self::assertSame($now, $options[self::HEADERS]['Bitvavo-Access-Timestamp']);
                        self::assertArrayHasKey('Bitvavo-Access-Window', $options[self::HEADERS]);
                        self::assertSame($this->accessWindow, $options[self::HEADERS]['Bitvavo-Access-Window']);
                        self::assertArrayHasKey('Content-Type', $options[self::HEADERS]);
                        self::assertSame('application/json', $options[self::HEADERS]['Content-Type']);
                        self::assertArrayHasKey('User-Agent', $options[self::HEADERS]);

                        self::assertArrayHasKey('query', $options);
                        self::assertSame($parameters, $options['query']);

                        if (!empty($body)) {
                            self::assertArrayHasKey('json', $options);
                            self::assertSame($body, $options['json']);
                        }

                        return true;
                    }
                )
            )
            ->willReturn($mockResponse)
        ;

        $response = $this->client->apiCall($path, $method, $parameters, $body, $now);

        static::assertSame($returnData, $response);
    }

    /**
     * @covers ::apiCall
     * @dataProvider differentApiCalls
     */
    public function testApiCallThrowsExceptionIfBitvavoReturnsError(
        string $method,
        array $parameters,
        array $body
    ): void {
        $path = 'path'.random_int(1000, 2000);
        $errorCode = random_int(12, 24);
        $error = 'error'.random_int(1000, 2000);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse
            ->expects(static::once())
            ->method('toArray')
            ->willReturn(['error' => $error, 'errorCode' => $errorCode])
        ;

        $this->httpClient
            ->expects(static::once())
            ->method('request')
            ->willReturn($mockResponse)
        ;

        $this->expectException(BitvavoClientException::class);
        $this->expectExceptionCode($errorCode);
        $this->expectExceptionMessage($error);

        $this->client->apiCall($path, $method, $parameters, $body);
    }
}

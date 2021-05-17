<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Client;

use Jorijn\Bitcoin\Dca\Client\BinanceClient;
use Jorijn\Bitcoin\Dca\Exception\BinanceClientException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Client\BinanceClient
 * @covers ::__construct
 *
 * @internal
 */
final class BinanceClientTest extends TestCase
{
    private string $apiKey;
    private string $apiSecret;
    /** @var HttpClientInterface|MockObject */
    private $httpClient;
    private BinanceClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = 'api_key_'.random_int(1000, 2000);
        $this->apiSecret = 'api_secret_'.random_int(1000, 2000);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->client = new BinanceClient($this->httpClient, $this->apiKey, $this->apiSecret);
    }

    /**
     * @covers ::parse
     * @covers ::request
     */
    public function testUserAgentIsAddedToRequest(): void
    {
        $this->httpClient
            ->expects(static::once())
            ->method('request')
            ->with('GET', 'foo', static::callback(function (array $options) {
                self::assertArrayHasKey('headers', $options);
                self::assertArrayHasKey('User-Agent', $options['headers']);
                self::assertSame(BinanceClient::USER_AGENT, $options['headers']['User-Agent']);

                return true;
            }))
            ->willReturn($this->getResponseMock())
        ;

        $this->client->request('GET', 'foo', []);
    }

    /**
     * @covers ::parse
     * @covers ::request
     */
    public function testErrorIsWrappedInException(): void
    {
        $errorCode = random_int(100, 200);
        $errorMessage = 'error_message_'.random_int(1000, 2000);

        $this->httpClient
            ->expects(static::once())
            ->method('request')
            ->willReturn($this->getResponseMock(['msg' => $errorMessage, 'code' => $errorCode]))
        ;

        $this->expectException(BinanceClientException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($errorCode);

        $this->client->request('GET', 'foo', []);
    }

    /**
     * @covers ::addApiKeyToRequest
     * @covers ::addSignatureToRequest
     * @covers ::parse
     * @covers ::request
     */
    public function testSignatureAndApiKeyIsAddedToRequest(): void
    {
        $path = 'path_'.random_int(1000, 2000);
        $body = ['foo' => 'bar_'.random_int(1000, 2000)];

        $this->httpClient
            ->expects(static::once())
            ->method('request')
            ->with('GET', $path, static::callback(function (array $options) {
                self::assertArrayHasKey('query', $options);
                self::assertArrayHasKey('headers', $options);
                self::assertArrayHasKey('timestamp', $options['query']);
                self::assertArrayHasKey('signature', $options['query']);
                self::assertArrayHasKey('X-MBX-APIKEY', $options['headers']);
                self::assertSame($this->apiKey, $options['headers']['X-MBX-APIKEY']);

                return true;
            }))
            ->willReturn($this->getResponseMock())
        ;

        $this->client->request('GET', $path, ['extra' => ['security_type' => 'TRADE'], 'body' => $body]);
    }

    /**
     * @covers ::addApiKeyToRequest
     * @covers ::addSignatureToRequest
     * @covers ::parse
     * @covers ::request
     */
    public function testCredentialsAreAddedToPostRequest(): void
    {
        $path = 'path_'.random_int(1000, 2000);
        $body = ['foo' => 'bar_'.random_int(1000, 2000)];

        $this->httpClient
            ->expects(static::once())
            ->method('request')
            ->with('POST', $path, static::callback(function (array $options) {
                self::assertArrayHasKey('body', $options);
                self::assertArrayHasKey('timestamp', $options['body']);
                self::assertArrayHasKey('signature', $options['body']);

                return true;
            }))
            ->willReturn($this->getResponseMock())
        ;

        $this->client->request('POST', $path, ['extra' => ['security_type' => 'TRADE'], 'body' => $body]);
    }

    /**
     * @covers ::addSignatureToRequest
     * @covers ::parse
     * @covers ::request
     */
    public function testSignatureAddThrowsExceptionOnCorruptBody(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->client->request('GET', 'foo', ['extra' => ['security_type' => 'TRADE'], 'body' => 'foo']);
    }

    private function getResponseMock(array $response = []): ResponseInterface
    {
        $mock = $this->createMock(ResponseInterface::class);
        $mock->method('toArray')->willReturn($response);

        return $mock;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Client;

use Jorijn\Bitcoin\Dca\Client\KrakenClient;
use Jorijn\Bitcoin\Dca\Exception\KrakenClientException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Client\KrakenClient
 * @covers ::__construct
 *
 * @internal
 */
final class KrakenClientTest extends TestCase
{
    /** @var MockHttpClient */
    protected MockHttpClient $httpClient;
    /** @var LoggerInterface|MockObject */
    protected $logger;
    protected KrakenClient $client;
    protected string $version;
    private array $testResponses = [];
    private const BASE_URI = 'https://unit.test/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->version = (string) random_int(1, 10);

        $this->httpClient = new MockHttpClient(
            fn (
                $method,
                $url,
                $options
            ) => new MockResponse($this->testResponses[$method][$url] ?? []),
            self::BASE_URI
        );

        $this->client = new KrakenClient(
            $this->httpClient,
            $this->logger,
            'pk'.random_int(1000, 2000),
            'privkey'.random_int(1000, 2000),
            $this->version
        );
    }

    /**
     * @covers ::queryPublic
     * @covers ::validateResponse
     */
    public function testErrorsAreHandled(): void
    {
        $errorMessage = 'something went wrong!'.random_int(1000, 2000);

        $this->expectException(KrakenClientException::class);
        $this->expectExceptionMessage($errorMessage);

        $this->testResponses['POST'] = [
            self::BASE_URI.$this->version.'/public/test' => json_encode(
                ['error' => [$errorMessage]],
                JSON_THROW_ON_ERROR
            ),
        ];

        $this->client->queryPublic('test');
    }

    /**
     * @covers ::queryPublic
     * @covers ::validateResponse
     */
    public function testPublicApiMethodsWork(): void
    {
        $expectedResult = 'ok'.random_int(1000, 2000);

        $this->testResponses['POST'] = [
            self::BASE_URI.$this->version.'/public/test' => json_encode(
                ['result' => ['ok' => $expectedResult]],
                JSON_THROW_ON_ERROR
            ),
        ];

        static::assertSame(['ok' => $expectedResult], $this->client->queryPublic('test'));
    }

    /**
     * @covers ::queryPrivate
     * @covers ::validateResponse
     */
    public function testPrivateApiMethodsWork(): void
    {
        $expectedResult = 'ok'.random_int(1000, 2000);

        $this->testResponses['POST'] = [
            self::BASE_URI.$this->version.'/private/test' => json_encode(
                ['result' => ['ok' => $expectedResult]],
                JSON_THROW_ON_ERROR
            ),
        ];

        static::assertSame(['ok' => $expectedResult], $this->client->queryPrivate('test'));
    }
}

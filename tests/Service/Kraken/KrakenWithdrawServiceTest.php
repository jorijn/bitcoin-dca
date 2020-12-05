<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Service\Kraken\KrakenWithdrawService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Kraken\KrakenWithdrawService
 * @covers ::__construct
 *
 * @internal
 */
final class KrakenWithdrawServiceTest extends TestCase
{
    /** @var KrakenClientInterface|MockObject */
    private $client;
    /** @var LoggerInterface|MockObject */
    private $logger;
    private string $withdrawKey;
    private KrakenWithdrawService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(KrakenClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->withdrawKey = 'wk'.random_int(1000, 2000);

        $this->service = new KrakenWithdrawService($this->client, $this->logger, $this->withdrawKey);
    }
}

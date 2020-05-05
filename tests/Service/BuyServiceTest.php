<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Service;

use Jorijn\Bl3pDca\Client\Bl3pClientInterface;
use Jorijn\Bl3pDca\Service\BuyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Service\BuyService
 *
 * @internal
 */
final class BuyServiceTest extends TestCase
{
    /** @var Bl3pClientInterface|MockObject */
    private $client;
    /** @var LoggerInterface|MockObject */
    private $logger;
    /** @var EventDispatcherInterface|MockObject */
    private $dispatcher;
    private int $timeout;
    private BuyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Bl3pClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->timeout = 2;

        $this->service = new BuyService(
            $this->client,
            $this->dispatcher,
            $this->logger,
            $this->timeout
        );
    }

    public function testBuyFillDelayedButWithinTimeout(): void
    {
    }

    public function testBuyCannotBeFilledWithinTimeout(): void
    {
    }

    public function testBuyIsFilledImmediately(): void
    {
    }
}

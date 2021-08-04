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

namespace Tests\Jorijn\Bitcoin\Dca\Service;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\Exception\BuyTimeoutException;
use Jorijn\Bitcoin\Dca\Exception\NoExchangeAvailableException;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyService;
use Jorijn\Bitcoin\Dca\Service\BuyServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\BuyService
 * @covers ::__construct
 *
 * @internal
 */
final class BuyServiceTest extends TestCase
{
    /** @var EventDispatcherInterface|MockObject */
    private $dispatcher;
    /** @var LoggerInterface|MockObject */
    private $logger;
    private string $configuredExchange;
    /** @var BuyServiceInterface|MockObject */
    private $supportedService;
    private int $timeout;
    private BuyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configuredExchange = 'cw'.random_int(1000, 2000);
        $this->supportedService = $this->createMock(BuyServiceInterface::class);
        $this->timeout = 5;

        $this->supportedService->method('supportsExchange')->with($this->configuredExchange)->willReturn(true);

        $this->service = new BuyService(
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange,
            [$this->supportedService],
            $this->timeout
        );
    }

    public function providerOfBuyScenarios(): array
    {
        return [
            'buy fills immediately' => [0, false],
            'buy fills after three seconds' => [3, false],
            'buy fills after seven seconds, but timeout is set at 5 -> timeout + cancellation' => [7, true],
            'buy fills immediately but fees settled in EUR' => [0, false],
        ];
    }

    /**
     * @dataProvider providerOfBuyScenarios
     * @covers ::buy
     * @covers ::buyAtService
     */
    public function testBuyWithVariousOptions(int $buyFillsAfter, bool $expectCancellation): void
    {
        $buyOrderDTO = new CompletedBuyOrder();
        $amount = random_int(1000, 2000);
        $orderId = 'oid'.random_int(1000, 2000);
        $start = time();
        $tag = 'tag'.random_int(1000, 2000);

        $this->supportedService
            ->expects(static::once())
            ->method('initiateBuy')
            ->with($amount)
            ->willReturnCallback(static function () use ($orderId, $buyOrderDTO, $buyFillsAfter) {
                if (0 === $buyFillsAfter) {
                    return $buyOrderDTO;
                }

                throw new PendingBuyOrderException($orderId);
            })
        ;

        $this->supportedService
            ->expects($buyFillsAfter > 0 ? static::atLeastOnce() : static::never())
            ->method('checkIfOrderIsFilled')
            ->with($orderId)
            ->willReturnCallback(static function () use ($orderId, $buyOrderDTO, $start, $buyFillsAfter) {
                if (time() >= ($start + $buyFillsAfter)) {
                    return $buyOrderDTO;
                }

                throw new PendingBuyOrderException($orderId);
            })
        ;

        if ($expectCancellation) {
            $this->supportedService
                ->expects(static::once())
                ->method('cancelBuyOrder')
                ->with($orderId)
            ;

            $this->logger
                ->expects(static::atLeastOnce())
                ->method('error')
            ;

            $this->expectException(BuyTimeoutException::class);
        } else {
            $this->dispatcher
                ->expects(static::once())
                ->method('dispatch')
                ->with(static::callback(static function (BuySuccessEvent $event) use ($tag, $buyOrderDTO) {
                    self::assertSame($buyOrderDTO, $event->getBuyOrder());
                    self::assertSame($tag, $event->getTag());

                    return true;
                }))
            ;
        }

        static::assertSame($buyOrderDTO, $this->service->buy($amount, $tag));
    }

    /**
     * @covers ::buy
     */
    public function testNoSupportedExchange(): void
    {
        $unsupportedService = $this->createMock(BuyServiceInterface::class);
        $unsupportedService
            ->expects(static::once())
            ->method('supportsExchange')
            ->with($this->configuredExchange)
            ->willReturn(false)
        ;

        $localService = new BuyService(
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange,
            [$unsupportedService],
            $this->timeout
        );

        $this->logger->expects(static::atLeastOnce())->method('error');
        $this->expectException(NoExchangeAvailableException::class);

        $localService->buy(10);
    }
}

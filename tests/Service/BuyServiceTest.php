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
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\BuyService
 *
 * @covers ::__construct
 *
 * @internal
 */
final class BuyServiceTest extends TestCase
{
    private \Psr\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher;

    private \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;
    private string $configuredExchange;

    private \Jorijn\Bitcoin\Dca\Service\BuyServiceInterface|\PHPUnit\Framework\MockObject\MockObject $supportedService;
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
     *
     * @covers ::buy
     * @covers ::buyAtService
     */
    public function testBuyWithVariousOptions(int $buyFillsAfter, bool $expectCancellation): void
    {
        $completedBuyOrder = new CompletedBuyOrder();
        $amount = random_int(1000, 2000);
        $orderId = 'oid'.random_int(1000, 2000);
        $start = time();
        $tag = 'tag'.random_int(1000, 2000);

        $this->supportedService
            ->expects(static::once())
            ->method('initiateBuy')
            ->with($amount)
            ->willReturnCallback(static function () use ($orderId, $completedBuyOrder, $buyFillsAfter): \Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder {
                if (0 === $buyFillsAfter) {
                    return $completedBuyOrder;
                }

                throw new PendingBuyOrderException($orderId);
            })
        ;

        $this->supportedService
            ->expects($buyFillsAfter > 0 ? static::atLeastOnce() : static::never())
            ->method('checkIfOrderIsFilled')
            ->with($orderId)
            ->willReturnCallback(static function () use ($orderId, $completedBuyOrder, $start, $buyFillsAfter): \Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder {
                if (time() >= ($start + $buyFillsAfter)) {
                    return $completedBuyOrder;
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
                ->with(
                    static::callback(static function (BuySuccessEvent $event) use ($tag, $completedBuyOrder): bool {
                        self::assertSame($completedBuyOrder, $event->getBuyOrder());
                        self::assertSame($tag, $event->getTag());

                        return true;
                    })
                )
            ;
        }

        static::assertSame($completedBuyOrder, $this->service->buy($amount, $tag));
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

        $buyService = new BuyService(
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange,
            [$unsupportedService],
            $this->timeout
        );

        $this->logger->expects(static::atLeastOnce())->method('error');
        $this->expectException(NoExchangeAvailableException::class);

        $buyService->buy(10);
    }
}

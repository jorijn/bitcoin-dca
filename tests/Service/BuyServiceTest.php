<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\Exception\NoExchangeAvailableException;
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
    private string $baseCurrency;
    private int $timeout;
    private string $configuredExchange;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->baseCurrency = 'bc'.random_int(1000, 2000);
        $this->timeout = 30;
        $this->configuredExchange = 'ce'.random_int(1000, 2000);
    }

    /**
     * @covers ::buy
     *
     * @throws \Exception
     */
    public function testNoSupportedExchanges(): void
    {
        $unsupportedService = $this->createMock(BuyServiceInterface::class);
        $service = new BuyService(
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange,
            [$unsupportedService],
            $this->timeout,
            $this->baseCurrency
        );

        $unsupportedService
            ->expects(static::once())
            ->method('supportsExchange')
            ->with($this->configuredExchange)
            ->willReturn(false)
        ;

        $this->logger->expects(static::atLeastOnce())->method('error');
        $this->expectException(NoExchangeAvailableException::class);

        $service->buy(random_int(1000, 2000));
    }

    /**
     * @dataProvider providerOfTags
     * @covers ::buy
     *
     * @throws \Exception
     */
    public function testBuyHappyFlow(string $tag = null): void
    {
        $supportedService = $this->createMock(BuyServiceInterface::class);
        $unsupportedService = $this->createMock(BuyServiceInterface::class);
        $amount = random_int(1000, 2000);

        $service = new BuyService(
            $this->dispatcher,
            $this->logger,
            $this->configuredExchange,
            [$unsupportedService, $supportedService],
            $this->timeout,
            $this->baseCurrency
        );

        $unsupportedService
            ->expects(static::once())
            ->method('supportsExchange')
            ->with($this->configuredExchange)
            ->willReturn(false)
        ;

        $supportedService
            ->expects(static::once())
            ->method('supportsExchange')
            ->with($this->configuredExchange)
            ->willReturn(true)
        ;

        $orderDTO = new CompletedBuyOrder();
        $supportedService
            ->expects(static::once())
            ->method('initiateBuy')
            ->with($amount, $this->baseCurrency, $this->timeout)
            ->willReturn($orderDTO)
        ;

        $this->logger->expects(static::atLeastOnce())->method('info');

        $this->dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(static function (BuySuccessEvent $event) use ($orderDTO, $tag) {
                self::assertSame($tag, $event->getTag());
                self::assertSame($orderDTO, $event->getBuyOrder());

                return true;
            }))
        ;

        $service->buy($amount, $tag);
    }

    public function providerOfTags(): array
    {
        return [
            'with tag' => ['tag'.random_int(1000, 2000)],
            'without tag' => [null],
        ];
    }
}

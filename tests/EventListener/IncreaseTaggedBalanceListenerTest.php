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

namespace Tests\Jorijn\Bitcoin\Dca\EventListener;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\EventListener\IncreaseTaggedBalanceListener;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\IncreaseTaggedBalanceListener
 * @covers ::__construct
 *
 * @internal
 */
final class IncreaseTaggedBalanceListenerTest extends TestCase
{
    /** @var MockObject|TaggedIntegerRepositoryInterface */
    private $repository;
    /** @var LoggerInterface|MockObject */
    private $logger;
    private IncreaseTaggedBalanceListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(TaggedIntegerRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new IncreaseTaggedBalanceListener($this->repository, $this->logger);
    }

    /**
     * @covers ::onBalanceIncrease
     */
    public function testBalanceIsIncreasedForTag(): void
    {
        $tag = 'tag'.random_int(1000, 2000);
        $amount = random_int(1000, 2000);
        $fees = random_int(0, 999);

        $completedBuyOrder = (new CompletedBuyOrder())
            ->setAmountInSatoshis($amount)
            ->setFeesInSatoshis($fees)
        ;

        $this->repository
            ->expects(static::once())
            ->method('increase')
            ->with($tag, $amount - $fees)
        ;

        $this->logger
            ->expects(static::once())
            ->method('info')
        ;

        $this->listener->onBalanceIncrease(new BuySuccessEvent($completedBuyOrder, $tag));
    }

    /**
     * @covers ::onBalanceIncrease
     */
    public function testListenerDoesNotActWithoutTag(): void
    {
        $this->repository->expects(static::never())->method('increase');
        $this->listener->onBalanceIncrease(new BuySuccessEvent(new CompletedBuyOrder()));
    }
}

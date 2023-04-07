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

use Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent;
use Jorijn\Bitcoin\Dca\EventListener\ResetTaggedBalanceListener;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\ResetTaggedBalanceListener
 *
 * @covers ::__construct
 *
 * @internal
 */
final class ResetTaggedBalanceListenerTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject|\Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface $repository;

    private \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;
    private ResetTaggedBalanceListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(TaggedIntegerRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new ResetTaggedBalanceListener($this->repository, $this->logger);
    }

    /**
     * @covers ::onWithdrawSucces
     */
    public function testListenerDoesNotActWithoutTag(): void
    {
        $withdrawSuccessEvent = new WithdrawSuccessEvent($this->createMock(CompletedWithdraw::class));

        $this->repository
            ->expects(static::never())
            ->method('set')
        ;

        $this->listener->onWithdrawSucces($withdrawSuccessEvent);
    }

    /**
     * @covers ::onWithdrawSucces
     *
     * @throws \Exception
     */
    public function testListenerResetsBalanceForTag(): void
    {
        $tag = 'tag'.random_int(1000, 2000);
        $withdrawSuccessEvent = new WithdrawSuccessEvent($this->createMock(CompletedWithdraw::class), $tag);

        $this->repository
            ->expects(static::once())
            ->method('set')
            ->with($tag, 0)
        ;

        $this->logger
            ->expects(static::atLeastOnce())
            ->method('info')
        ;

        $this->listener->onWithdrawSucces($withdrawSuccessEvent);
    }
}

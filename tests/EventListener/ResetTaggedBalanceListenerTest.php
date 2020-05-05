<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\EventListener;

use Jorijn\Bl3pDca\Event\WithdrawSuccessEvent;
use Jorijn\Bl3pDca\EventListener\ResetTaggedBalanceListener;
use Jorijn\Bl3pDca\Model\CompletedWithdraw;
use Jorijn\Bl3pDca\Repository\TaggedIntegerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\EventListener\ResetTaggedBalanceListener
 * @covers ::__construct
 *
 * @internal
 */
final class ResetTaggedBalanceListenerTest extends TestCase
{
    /** @var MockObject|TaggedIntegerRepositoryInterface */
    private $repository;
    /** @var LoggerInterface|MockObject */
    private $logger;
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
        $event = new WithdrawSuccessEvent($this->createMock(CompletedWithdraw::class));

        $this->repository
            ->expects(static::never())
            ->method('set')
        ;

        $this->listener->onWithdrawSucces($event);
    }

    /**
     * @covers ::onWithdrawSucces
     *
     * @throws \Exception
     */
    public function testListenerResetsBalanceForTag(): void
    {
        $tag = 'tag'.random_int(1000, 2000);
        $event = new WithdrawSuccessEvent($this->createMock(CompletedWithdraw::class), $tag);

        $this->repository
            ->expects(static::once())
            ->method('set')
            ->with($tag, 0)
        ;

        $this->logger
            ->expects(static::atLeastOnce())
            ->method('info')
        ;

        $this->listener->onWithdrawSucces($event);
    }
}

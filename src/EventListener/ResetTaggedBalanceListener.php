<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\EventListener;

use Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Psr\Log\LoggerInterface;

class ResetTaggedBalanceListener
{
    protected TaggedIntegerRepositoryInterface $repository;
    protected LoggerInterface $logger;

    public function __construct(TaggedIntegerRepositoryInterface $repository, LoggerInterface $logger)
    {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function onWithdrawSucces(WithdrawSuccessEvent $event): void
    {
        $tag = $event->getTag();

        if (!$tag) {
            return;
        }

        $this->repository->set($tag, 0);

        $this->logger->info('reset tagged balance for tag {tag}', ['tag' => $tag]);
    }
}

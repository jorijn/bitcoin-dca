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

namespace Jorijn\Bitcoin\Dca\EventListener;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Psr\Log\LoggerInterface;

class IncreaseTaggedBalanceListener
{
    protected TaggedIntegerRepositoryInterface $repository;
    protected LoggerInterface $logger;

    public function __construct(TaggedIntegerRepositoryInterface $repository, LoggerInterface $logger)
    {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function onBalanceIncrease(BuySuccessEvent $event): void
    {
        if (!$tag = $event->getTag()) {
            return;
        }

        $buyOrder = $event->getBuyOrder();
        $netAmount = $buyOrder->getAmountInSatoshis() - $buyOrder->getFeesInSatoshis();

        $this->repository->increase($tag, $netAmount);

        $this->logger->info('increased balance for tag {tag} with {balance} satoshis', [
            'tag' => $tag,
            'balance' => $netAmount,
        ]);
    }
}

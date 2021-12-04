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
    public function __construct(
        protected TaggedIntegerRepositoryInterface $taggedIntegerRepository,
        protected LoggerInterface $logger
    ) {
    }

    public function onBalanceIncrease(BuySuccessEvent $buySuccessEvent): void
    {
        if (!$tag = $buySuccessEvent->getTag()) {
            return;
        }

        $buyOrder = $buySuccessEvent->getBuyOrder();
        $netAmount = $buyOrder->getAmountInSatoshis() - $buyOrder->getFeesInSatoshis();

        $this->taggedIntegerRepository->increase($tag, $netAmount);

        $this->logger->info('increased balance for tag {tag} with {balance} satoshis', [
            'tag' => $tag,
            'balance' => $netAmount,
        ]);
    }
}

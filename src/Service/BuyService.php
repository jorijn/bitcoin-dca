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

namespace Jorijn\Bitcoin\Dca\Service;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\Exception\BuyTimeoutException;
use Jorijn\Bitcoin\Dca\Exception\NoExchangeAvailableException;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class BuyService
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected LoggerInterface $logger,
        protected string $configuredExchange,
        protected iterable $registeredServices = [],
        protected int $timeout = 30
    ) {
    }

    public function buy(int $amount, string $tag = null): CompletedBuyOrder
    {
        $logContext = [
            'exchange' => $this->configuredExchange,
            'amount' => $amount,
            'tag' => $tag,
        ];

        $this->logger->info('performing buy for {amount}', $logContext);

        foreach ($this->registeredServices as $registeredService) {
            if ($registeredService->supportsExchange($this->configuredExchange)) {
                $this->logger->info('found service that supports buying for {exchange}', $logContext);

                $buyOrder = $this->buyAtService($registeredService, $amount);
                $this->eventDispatcher->dispatch(new BuySuccessEvent($buyOrder, $tag));

                return $buyOrder;
            }
        }

        $errorMessage = 'no exchange was available to perform this buy';
        $this->logger->error($errorMessage, $logContext);

        throw new NoExchangeAvailableException($errorMessage);
    }

    protected function buyAtService(
        BuyServiceInterface $buyService,
        int $amount,
        int $try = 0,
        int $start = null,
        string $orderId = null
    ): CompletedBuyOrder {
        if (null === $start) {
            $start = time();
        }

        try {
            $buyOrder = 0 === $try ? $buyService->initiateBuy($amount) : $buyService->checkIfOrderIsFilled(
                (string) $orderId
            );
        } catch (PendingBuyOrderException $exception) {
            if (time() < ($start + $this->timeout)) {
                sleep(1);

                return $this->buyAtService($buyService, $amount, ++$try, $start, $exception->getOrderId());
            }

            $buyService->cancelBuyOrder($exception->getOrderId());

            $error = 'buy did not fill within given timeout';
            $this->logger->error($error);

            throw new BuyTimeoutException($error);
        }

        return $buyOrder;
    }
}

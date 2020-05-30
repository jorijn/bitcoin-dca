<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\Exception\NoExchangeAvailableException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class BuyService
{
    protected LoggerInterface $logger;
    protected EventDispatcherInterface $dispatcher;
    protected int $timeout;
    protected string $baseCurrency;
    protected string $configuredExchange;
    /** @var BuyServiceInterface[]|iterable */
    protected $registeredServices;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        string $configuredExchange,
        iterable $registeredServices = [],
        int $timeout = 30,
        string $baseCurrency = 'EUR'
    ) {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->timeout = $timeout;
        $this->baseCurrency = $baseCurrency;
        $this->registeredServices = $registeredServices;
        $this->configuredExchange = $configuredExchange;
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

                $buyOrder = $registeredService->buy($amount, $this->baseCurrency, $this->timeout);
                $this->dispatcher->dispatch(new BuySuccessEvent($buyOrder, $tag));

                return $buyOrder;
            }
        }

        $errorMessage = 'no exchange was available to perform this buy';
        $this->logger->error($errorMessage, $logContext);

        throw new NoExchangeAvailableException($errorMessage);
    }
}

<?php

declare(strict_types=1);

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
    protected LoggerInterface $logger;
    protected EventDispatcherInterface $dispatcher;
    protected int $timeout;
    protected string $configuredExchange;
    /** @var BuyServiceInterface[]|iterable */
    protected $registeredServices;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger,
        string $configuredExchange,
        iterable $registeredServices = [],
        int $timeout = 30
    ) {
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->timeout = $timeout;
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

                $buyOrder = $this->buyAtService($registeredService, $amount);
                $this->dispatcher->dispatch(new BuySuccessEvent($buyOrder, $tag));

                return $buyOrder;
            }
        }

        $errorMessage = 'no exchange was available to perform this buy';
        $this->logger->error($errorMessage, $logContext);

        throw new NoExchangeAvailableException($errorMessage);
    }

    protected function buyAtService(BuyServiceInterface $service, int $amount, int $try = 0, int $start = null, string $orderId = null): CompletedBuyOrder
    {
        if (null === $start) {
            $start = time();
        }

        try {
            if (0 === $try) {
                $buyOrder = $service->initiateBuy($amount);
            } else {
                $buyOrder = $service->checkIfOrderIsFilled((string) $orderId);
            }
        } catch (PendingBuyOrderException $exception) {
            if (time() < ($start + $this->timeout)) {
                sleep(1);

                return $this->buyAtService($service, $amount, ++$try, $start, $exception->getOrderId());
            }

            $service->cancelBuyOrder($exception->getOrderId());

            $error = 'buy did not fill within given timeout';
            $this->logger->error($error);

            throw new BuyTimeoutException($error);
        }

        return $buyOrder;
    }
}

<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Service;

use Jorijn\Bl3pDca\Client\Bl3pClientInterface;
use Jorijn\Bl3pDca\Event\BuySuccessEvent;
use Jorijn\Bl3pDca\Exception\BuyTimeoutException;
use Jorijn\Bl3pDca\Model\CompletedBuyOrder;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class BuyService
{
    public const ORDER_TIMEOUT = 30;
    public const ORDER_ID = 'order_id';
    public const ORDER_DATA = 'order_data';
    public const TAG = 'tag';
    public const TOTAL_FEE = 'total_fee';
    public const DATA = 'data';
    public const TOTAL_AMOUNT = 'total_amount';
    public const VALUE_INT = 'value_int';
    public const CURRENCY = 'currency';
    public const DISPLAY = 'display';
    public const TOTAL_SPENT = 'total_spent';
    public const DISPLAY_SHORT = 'display_short';
    public const AVG_COST = 'avg_cost';
    public const ORDER_STATUS_CLOSED = 'closed';

    protected Bl3pClientInterface $client;
    protected LoggerInterface $logger;
    protected EventDispatcherInterface $dispatcher;

    public function __construct(
        Bl3pClientInterface $client,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    public function buy(int $amount, string $tag = null): CompletedBuyOrder
    {
        $params = [
            'type' => 'bid',
            'amount_funds_int' => $amount * 100000,
            'fee_currency' => 'BTC',
        ];

        // TODO: be more defensive about this part, stuff could break here and no one likes error messages when it comes to money
        $result = $this->client->apiCall('BTCEUR/money/order/add', $params);

        // fetch the order info and wait until the order has been filled
        $failureAt = time() + self::ORDER_TIMEOUT;

        do {
            $orderInfo = $this->client->apiCall('BTCEUR/money/order/result', [
                self::ORDER_ID => $result[self::DATA][self::ORDER_ID],
            ]);

            if (self::ORDER_STATUS_CLOSED === $orderInfo[self::DATA]['status']) {
                break;
            }

            $this->logger->info(
                'order still open, waiting a maximum of {seconds} for it to fill',
                [
                    'seconds' => self::ORDER_TIMEOUT,
                    self::ORDER_DATA => $orderInfo[self::DATA],
                    self::TAG => $tag,
                ]
            );

            sleep(1);
        } while (time() < $failureAt);

        if (self::ORDER_STATUS_CLOSED === $orderInfo[self::DATA]['status']) {
            $this->logger->info(
                'order filled, successfully bought bitcoin',
                [self::TAG => $tag, self::ORDER_DATA => $orderInfo[self::DATA]]
            );

            $buyOrder = (new CompletedBuyOrder())
                ->setAmountInSatoshis((int)$orderInfo[self::DATA][self::TOTAL_AMOUNT][self::VALUE_INT])
                ->setFeesInSatoshis('BTC' === $orderInfo[self::DATA][self::TOTAL_FEE][self::CURRENCY]
                    ? (int)$orderInfo[self::DATA][self::TOTAL_FEE][self::VALUE_INT]
                    : 0)
                ->setDisplayAmountBought($orderInfo[self::DATA][self::TOTAL_AMOUNT][self::DISPLAY])
                ->setDisplayAmountSpent($orderInfo[self::DATA][self::TOTAL_SPENT][self::DISPLAY_SHORT])
                ->setDisplayAveragePrice($orderInfo[self::DATA][self::AVG_COST][self::DISPLAY_SHORT])
                ->setDisplayFeesSpent($orderInfo[self::DATA][self::TOTAL_FEE][self::DISPLAY]);

            $this->dispatcher->dispatch(new BuySuccessEvent($buyOrder, $tag));

            return $buyOrder;
        }

        $this->client->apiCall('BTCEUR/money/order/cancel', [self::ORDER_ID => $result[self::DATA][self::ORDER_ID]]);

        $error = 'was not able to fill a MARKET order within the specified timeout, the order was cancelled';
        $this->logger->error(
            $error,
            [self::TAG => $tag, self::ORDER_DATA => $orderInfo[self::DATA]]
        );

        throw new BuyTimeoutException($error);
    }
}

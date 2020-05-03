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

        // FIXME: be more defensive about this part, stuff could break here and no one likes error messages when it comes to money
        $result = $this->client->apiCall('BTCEUR/money/order/add', $params);

        // fetch the order info and wait until the order has been filled
        $failureAt = time() + self::ORDER_TIMEOUT;

        do {
            $orderInfo = $this->client->apiCall('BTCEUR/money/order/result', [
                'order_id' => $result['data']['order_id'],
            ]);

            if ('closed' === $orderInfo['data']['status']) {
                break;
            }

            $this->logger->info(
                'order still open, waiting a maximum of {seconds} for it to fill',
                [
                    'seconds' => self::ORDER_TIMEOUT,
                    'order_data' => $orderInfo['data'],
                    'tag' => $tag,
                ]
            );

            sleep(1);
        } while (time() < $failureAt);

        if ('closed' === $orderInfo['data']['status']) {
            $this->logger->info(
                'order filled, successfully bought bitcoin',
                ['tag' => $tag, 'order_data' => $orderInfo['data']]
            );

            $buyOrder = (new CompletedBuyOrder())
                ->setAmountInSatoshis((int) $orderInfo['data']['total_amount']['value_int'])
                ->setFeesInSatoshis('BTC' === $orderInfo['data']['total_fee']['currency']
                    ? (int) $orderInfo['data']['total_fee']['value_int']
                    : 0)
                ->setDisplayAmountBought($orderInfo['data']['total_amount']['display'])
                ->setDisplayAmountSpent($orderInfo['data']['total_spent']['display_short'])
                ->setDisplayAveragePrice($orderInfo['data']['avg_cost']['display_short'])
                ->setDisplayFeesSpent($orderInfo['data']['total_fee']['display'])
            ;

            $this->dispatcher->dispatch(new BuySuccessEvent($buyOrder, $tag));

            return $buyOrder;
        }

        $this->client->apiCall('BTCEUR/money/order/cancel', ['order_id' => $result['data']['order_id']]);

        $error = 'was not able to fill a MARKET order within the specified timeout, the order was cancelled';
        $this->logger->error(
            $error,
            ['tag' => $tag, 'order_data' => $orderInfo['data']]
        );

        throw new BuyTimeoutException($error);
    }
}

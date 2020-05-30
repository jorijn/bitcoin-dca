<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Bl3p;

use Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface;
use Jorijn\Bitcoin\Dca\Exception\BuyTimeoutException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyServiceInterface;
use Psr\Log\LoggerInterface;

class Bl3pBuyService implements BuyServiceInterface
{
    public const ORDER_ID = 'order_id';
    public const ORDER_DATA = 'order_data';
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
    public const STATUS = 'status';
    public const SECONDS = 'seconds';
    public const TYPE = 'type';
    public const AMOUNT_FUNDS_INT = 'amount_funds_int';
    public const FEE_CURRENCY = 'fee_currency';
    public const BL3P = 'bl3p';

    protected Bl3pClientInterface $client;
    protected LoggerInterface $logger;

    public function __construct(
        Bl3pClientInterface $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function supportsExchange(string $exchange): bool
    {
        return self::BL3P === $exchange;
    }

    public function buy(int $amount, string $baseCurrency, int $timeout): CompletedBuyOrder
    {
        $params = [
            self::TYPE => 'bid',
            self::AMOUNT_FUNDS_INT => $amount * 100000,
            self::FEE_CURRENCY => 'BTC',
        ];

        $tradingPair = sprintf('BTC%s', $baseCurrency);

        $result = $this->client->apiCall($tradingPair.'/money/order/add', $params);

        // fetch the order info and wait until the order has been filled
        $failureAt = time() + $timeout;

        do {
            $orderInfo = $this->client->apiCall($tradingPair.'/money/order/result', [
                self::ORDER_ID => $result[self::DATA][self::ORDER_ID],
            ]);

            if (self::ORDER_STATUS_CLOSED === $orderInfo[self::DATA][self::STATUS]) {
                break;
            }

            $this->logger->info(
                'order still open, waiting a maximum of {seconds} for it to fill',
                [
                    self::SECONDS => $timeout,
                    self::ORDER_DATA => $orderInfo[self::DATA],
                ]
            );

            sleep(1);
        } while (time() < $failureAt);

        if (self::ORDER_STATUS_CLOSED === $orderInfo[self::DATA][self::STATUS]) {
            $this->logger->info(
                'order filled, successfully bought bitcoin',
                [self::ORDER_DATA => $orderInfo[self::DATA]]
            );

            return (new CompletedBuyOrder())
                ->setAmountInSatoshis((int) $orderInfo[self::DATA][self::TOTAL_AMOUNT][self::VALUE_INT])
                ->setFeesInSatoshis('BTC' === $orderInfo[self::DATA][self::TOTAL_FEE][self::CURRENCY]
                    ? (int) $orderInfo[self::DATA][self::TOTAL_FEE][self::VALUE_INT]
                    : 0)
                ->setDisplayAmountBought($orderInfo[self::DATA][self::TOTAL_AMOUNT][self::DISPLAY])
                ->setDisplayAmountSpent($orderInfo[self::DATA][self::TOTAL_SPENT][self::DISPLAY_SHORT])
                ->setDisplayAveragePrice($orderInfo[self::DATA][self::AVG_COST][self::DISPLAY_SHORT])
                ->setDisplayFeesSpent($orderInfo[self::DATA][self::TOTAL_FEE][self::DISPLAY])
            ;
        }

        $this->client->apiCall(
            $tradingPair.'/money/order/cancel',
            [self::ORDER_ID => $result[self::DATA][self::ORDER_ID]]
        );

        $error = 'was not able to fill a MARKET order within the specified timeout, the order was cancelled';
        $this->logger->error(
            $error,
            [self::ORDER_DATA => $orderInfo[self::DATA]]
        );

        throw new BuyTimeoutException($error);
    }
}

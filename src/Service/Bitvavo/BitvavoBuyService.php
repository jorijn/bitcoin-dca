<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Bitvavo;

use Jorijn\Bitcoin\Dca\Client\BitvavoClientInterface;
use Jorijn\Bitcoin\Dca\Exception\BuyTimeoutException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyServiceInterface;
use Psr\Log\LoggerInterface;

class BitvavoBuyService implements BuyServiceInterface
{
    public const MARKET = 'market';
    public const FILLED_AMOUNT = 'filledAmount';
    public const ORDER_DATA = 'order_data';

    protected BitvavoClientInterface $client;
    protected LoggerInterface $logger;

    public function __construct(
        BitvavoClientInterface $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'bitvavo' === $exchange;
    }

    public function buy(int $amount, string $baseCurrency, int $timeout): CompletedBuyOrder
    {
        $tradingPair = sprintf('BTC-%s', $baseCurrency);
        $params = [
            self::MARKET => $tradingPair,
            'side' => 'buy',
            'orderType' => self::MARKET,
            'amountQuote' => (string) $amount,
        ];

        $orderInfo = $this->client->apiCall('order', 'POST', [], $params);

        // fetch the order info and wait until the order has been filled
        $failureAt = time() + $timeout;

        do {
            if ('filled' === $orderInfo['status']) {
                break;
            }

            // fetch the new information
            $orderInfo = $this->client->apiCall('order', 'GET', [
                self::MARKET => $tradingPair,
                'orderId' => $orderInfo['orderId'],
            ]);

            $this->logger->info(
                'order still open, waiting a maximum of {seconds} for it to fill',
                [
                    'seconds' => $timeout,
                    self::ORDER_DATA => $orderInfo,
                ]
            );

            sleep(1);
        } while (time() < $failureAt);

        if ('filled' === $orderInfo['status']) {
            $this->logger->info(
                'order filled, successfully bought bitcoin',
                [self::ORDER_DATA => $orderInfo]
            );

            return (new CompletedBuyOrder())
                ->setAmountInSatoshis((int) ($orderInfo[self::FILLED_AMOUNT] * 100000000))
                ->setFeesInSatoshis('BTC' === $orderInfo['feeCurrency']
                    ? (int) ($orderInfo['feePaid'] * 100000000)
                    : 0)
                ->setDisplayAmountBought($orderInfo[self::FILLED_AMOUNT].' BTC')
                ->setDisplayAmountSpent($orderInfo['filledAmountQuote'].' '.$baseCurrency)
                ->setDisplayAveragePrice($this->getAveragePrice($orderInfo).' '.$baseCurrency)
                ->setDisplayFeesSpent($orderInfo['feePaid'].' '.$orderInfo['feeCurrency'])
                ;
        }

        $this->client->apiCall('order', 'DELETE', [
            self::MARKET => $tradingPair,
            'orderId' => $orderInfo['orderId'],
        ]);

        $error = 'was not able to fill a MARKET order within the specified timeout, the order was cancelled';
        $this->logger->error(
            $error,
            [self::ORDER_DATA => $orderInfo]
        );

        throw new BuyTimeoutException($error);
    }

    protected function getAveragePrice($data): float
    {
        $dividend = $divisor = 0;
        $totalSats = $data[self::FILLED_AMOUNT] * 100000000;

        foreach ($data['fills'] as $fill) {
            $filledSats = $fill['amount'] * 100000000;
            $percent = ($filledSats / $totalSats) * 100;

            $dividend += ($percent * (float) $fill['price']);
            $divisor += $percent;
        }

        return $dividend / $divisor;
    }
}

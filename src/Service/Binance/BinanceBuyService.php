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

namespace Jorijn\Bitcoin\Dca\Service\Binance;

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Client\BinanceClientInterface;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyServiceInterface;

class BinanceBuyService implements BuyServiceInterface
{
    final public const ORDER_URL = 'api/v3/order';
    protected string $tradingPair;

    public function __construct(protected BinanceClientInterface $binanceClient, protected string $baseCurrency)
    {
        $this->tradingPair = sprintf('BTC%s', $this->baseCurrency);
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'binance' === $exchange;
    }

    public function initiateBuy(int $amount): CompletedBuyOrder
    {
        $response = $this->binanceClient->request('POST', self::ORDER_URL, [
            'extra' => ['security_type' => 'TRADE'],
            'body' => [
                'symbol' => $this->tradingPair,
                'side' => 'BUY',
                'type' => 'MARKET',
                'quoteOrderQty' => $amount,
                'newOrderRespType' => 'FULL',
            ],
        ]);

        if ('FILLED' !== $response['status']) {
            throw new PendingBuyOrderException($response['orderId']);
        }

        return $this->getCompletedBuyOrderFromResponse($response);
    }

    public function checkIfOrderIsFilled(string $orderId): CompletedBuyOrder
    {
        $response = $this->binanceClient->request('GET', self::ORDER_URL, [
            'extra' => ['security_type' => 'TRADE'],
            'body' => [
                'symbol' => $this->tradingPair,
                'orderId' => $orderId,
            ],
        ]);

        if ('FILLED' !== $response['status']) {
            throw new PendingBuyOrderException($response['orderId']);
        }

        $response['fills'] = $this->binanceClient->request('GET', 'api/v3/myTrades', [
            'extra' => ['security_type' => 'USER_DATA'],
            'body' => [
                'symbol' => $this->tradingPair,
                'startTime' => $response['time'],
            ],
        ]);

        return $this->getCompletedBuyOrderFromResponse($response);
    }

    public function cancelBuyOrder(string $orderId): void
    {
        $this->binanceClient->request('DELETE', self::ORDER_URL, [
            'extra' => ['security_type' => 'TRADE'],
            'body' => [
                'symbol' => $this->tradingPair,
                'orderId' => $orderId,
            ],
        ]);
    }

    protected function getCompletedBuyOrderFromResponse(array $orderInfo): CompletedBuyOrder
    {
        [$feeAmount, $feeCurrency] = $this->getFeeInformationFromOrderInfo($orderInfo);

        return (new CompletedBuyOrder())
            ->setAmountInSatoshis((int) bcmul((string) $orderInfo['executedQty'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS))
            ->setFeesInSatoshis(
                'BTC' === $feeCurrency
                    ? (int) bcmul((string) $feeAmount, Bitcoin::SATOSHIS, Bitcoin::DECIMALS)
                    : 0
            )
            ->setDisplayAmountBought($orderInfo['executedQty'].' BTC')
            ->setDisplayAmountSpent($orderInfo['cummulativeQuoteQty'].' '.$this->baseCurrency)
            ->setDisplayAveragePrice($this->getAveragePrice($orderInfo).' '.$this->baseCurrency)
            ->setDisplayFeesSpent($feeAmount.' '.$feeCurrency)
        ;
    }

    protected function getAveragePrice($data): float
    {
        $dividend = $divisor = 0;
        $totalSats = (int) bcmul((string) $data['executedQty'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS);

        foreach ($data['fills'] as $fill) {
            $filledSats = (int) bcmul((string) $fill['qty'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS);
            $percent = ($filledSats / $totalSats) * 100;

            $dividend += ($percent * (float) $fill['price']);
            $divisor += $percent;
        }

        return $dividend / $divisor;
    }

    protected function getFeeInformationFromOrderInfo(array $orderInfo): array
    {
        $feeCurrency = null;
        $fee = '0';

        foreach ($orderInfo['fills'] as $fill) {
            $feeDecimals = \strlen(explode('.', (string) $fill['commission'])[1]);
            $feeCurrency = $fill['commissionAsset'];
            $fee = bcadd($fee, (string) $fill['commission'], $feeDecimals);
        }

        return [$fee, $feeCurrency];
    }
}

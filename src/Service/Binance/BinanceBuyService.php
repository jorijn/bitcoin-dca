<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Binance;

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Client\BinanceClientInterface;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyServiceInterface;

class BinanceBuyService implements BuyServiceInterface
{
    protected BinanceClientInterface $client;
    protected string $baseCurrency;
    protected string $tradingPair;
    protected int $lastTransactTime;

    public function __construct(BinanceClientInterface $client, string $baseCurrency)
    {
        $this->client = $client;
        $this->baseCurrency = $baseCurrency;
        $this->tradingPair = sprintf('BTC%s', $this->baseCurrency);
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'binance' === $exchange;
    }

    public function initiateBuy(int $amount): CompletedBuyOrder
    {
        $response = $this->client->request('POST', 'api/v3/order', [
            'extra' => ['security_type' => 'TRADE'],
            'body' => [
                'symbol' => $this->tradingPair,
                'side' => 'BUY',
                'type' => 'MARKET',
                'quoteOrderQty' => $amount,
                'newOrderRespType' => 'FULL',
            ],
        ]);

        // save transaction time for checking order details
        $this->lastTransactTime = $response['transactTime'];

        if ('FILLED' !== $response['status']) {
            throw new PendingBuyOrderException($response['orderId']);
        }

        return $this->getCompletedBuyOrderFromResponse($response);
    }

    public function checkIfOrderIsFilled(string $orderId): CompletedBuyOrder
    {
        $response = $this->client->request('GET', 'api/v3/order', [
            'extra' => ['security_type' => 'TRADE'],
            'body' => [
                'symbol' => $this->tradingPair,
                'orderId' => $orderId,
            ],
        ]);

        if ('FILLED' !== $response['status']) {
            throw new PendingBuyOrderException($response['orderId']);
        }

        $response['fills'] = $this->client->request('GET', 'api/v3/myTrades', [
            'extra' => ['security_type' => 'USER_DATA'],
            'body' => [
                'symbol' => $this->tradingPair,
                'startTime' => $this->lastTransactTime ?? 1619940185745,
            ],
        ]);

        return $this->getCompletedBuyOrderFromResponse($response);
    }

    public function cancelBuyOrder(string $orderId): void
    {
        $this->client->request('DELETE', '/api/v3/order', [
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
            ->setAmountInSatoshis((int) bcmul($orderInfo['executedQty'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS))
            ->setFeesInSatoshis('BTC' === $feeCurrency
                ? (int) bcmul($feeAmount, Bitcoin::SATOSHIS, Bitcoin::DECIMALS)
                : 0)
            ->setDisplayAmountBought($orderInfo['executedQty'].' BTC')
            ->setDisplayAmountSpent($orderInfo['cummulativeQuoteQty'].' '.$this->baseCurrency)
            ->setDisplayAveragePrice($this->getAveragePrice($orderInfo).' '.$this->baseCurrency)
            ->setDisplayFeesSpent($feeAmount.' '.$feeCurrency)
        ;
    }

    protected function getAveragePrice($data): float
    {
        $dividend = $divisor = 0;
        $totalSats = (int) bcmul($data['executedQty'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS);

        foreach ($data['fills'] as $fill) {
            $filledSats = (int) bcmul($fill['qty'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS);
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
            $feeDecimals = \strlen(explode('.', $fill['commission'])[1]);
            $feeCurrency = $fill['commissionAsset'];
            $fee = bcadd($fee, $fill['commission'], $feeDecimals);
        }

        return [$fee, $feeCurrency];
    }
}

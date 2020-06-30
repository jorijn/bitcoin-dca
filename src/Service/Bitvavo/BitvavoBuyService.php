<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Bitvavo;

use Jorijn\Bitcoin\Dca\Client\BitvavoClientInterface;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyServiceInterface;

class BitvavoBuyService implements BuyServiceInterface
{
    public const MARKET = 'market';
    public const FILLED_AMOUNT = 'filledAmount';
    public const ORDER = 'order';
    public const ORDER_ID = 'orderId';
    private const SATOSHIS_IN_A_BITCOIN = '100000000';

    protected BitvavoClientInterface $client;
    protected string $baseCurrency;
    protected string $tradingPair;

    public function __construct(BitvavoClientInterface $client, string $baseCurrency)
    {
        $this->client = $client;
        $this->baseCurrency = $baseCurrency;
        $this->tradingPair = sprintf('BTC-%s', $this->baseCurrency);
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'bitvavo' === $exchange;
    }

    public function initiateBuy(int $amount): CompletedBuyOrder
    {
        return (new CompletedBuyOrder())
            ->setAmountInSatoshis(123456)
            ->setFeesInSatoshis(123)
            ->setDisplayAmountBought('0.000123456 BTC')
            ->setDisplayAmountSpent('7 EUR')
            ->setDisplayAveragePrice('3938 EUR')
            ->setDisplayFeesSpent('0.000000123 BTC')
        ;

        $orderInfo = $this->client->apiCall(self::ORDER, 'POST', [], [
            self::MARKET => $this->tradingPair,
            'side' => 'buy',
            'orderType' => self::MARKET,
            'amountQuote' => (string) $amount,
        ]);

        if ('filled' !== $orderInfo['status']) {
            throw new PendingBuyOrderException($orderInfo['orderId']);
        }

        return $this->getCompletedBuyOrderFromResponse($orderInfo);
    }

    public function checkIfOrderIsFilled(string $orderId): CompletedBuyOrder
    {
        $orderInfo = $this->client->apiCall(self::ORDER, 'GET', [
            self::MARKET => $this->tradingPair,
            self::ORDER_ID => $orderId,
        ]);

        if ('filled' !== $orderInfo['status']) {
            throw new PendingBuyOrderException($orderId);
        }

        return $this->getCompletedBuyOrderFromResponse($orderInfo);
    }

    public function cancelBuyOrder(string $orderId): void
    {
        $this->client->apiCall(self::ORDER, 'DELETE', [
            self::MARKET => $this->tradingPair,
            self::ORDER_ID => $orderId,
        ]);
    }

    protected function getCompletedBuyOrderFromResponse(array $orderInfo): CompletedBuyOrder
    {
        return (new CompletedBuyOrder())
            ->setAmountInSatoshis((int) bcmul($orderInfo[self::FILLED_AMOUNT], self::SATOSHIS_IN_A_BITCOIN, 8))
            ->setFeesInSatoshis('BTC' === $orderInfo['feeCurrency']
                ? (int) bcmul($orderInfo['feePaid'], self::SATOSHIS_IN_A_BITCOIN, 8)
                : 0)
            ->setDisplayAmountBought($orderInfo[self::FILLED_AMOUNT].' BTC')
            ->setDisplayAmountSpent($orderInfo['filledAmountQuote'].' '.$this->baseCurrency)
            ->setDisplayAveragePrice($this->getAveragePrice($orderInfo).' '.$this->baseCurrency)
            ->setDisplayFeesSpent($orderInfo['feePaid'].' '.$orderInfo['feeCurrency'])
        ;
    }

    protected function getAveragePrice($data): float
    {
        $dividend = $divisor = 0;
        $totalSats = (int) bcmul($data[self::FILLED_AMOUNT], self::SATOSHIS_IN_A_BITCOIN, 8);

        foreach ($data['fills'] as $fill) {
            $filledSats = (int) bcmul($fill['amount'], self::SATOSHIS_IN_A_BITCOIN, 8);
            $percent = ($filledSats / $totalSats) * 100;

            $dividend += ($percent * (float) $fill['price']);
            $divisor += $percent;
        }

        return $dividend / $divisor;
    }
}

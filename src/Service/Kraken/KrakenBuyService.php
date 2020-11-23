<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Exception\KrakenClientException;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyServiceInterface;

class KrakenBuyService implements BuyServiceInterface
{
    private const SATOSHIS_IN_A_BITCOIN = '100000000';
    protected string $lastUserRef;
    protected KrakenClientInterface $client;
    protected string $baseCurrency;
    protected string $tradingPair;

    public function __construct(KrakenClientInterface $client, string $baseCurrency)
    {
        $this->client = $client;
        $this->baseCurrency = $baseCurrency;
        $this->tradingPair = sprintf('XBT%s', $this->baseCurrency);
    }

    public function supportsExchange(string $exchange): bool
    {
        return 'kraken' === $exchange;
    }

    public function initiateBuy(int $amount): CompletedBuyOrder
    {
        // generate a 32-bit singed integer to track this order
        $this->lastUserRef = (string) random_int(0, 0x7FFFFFFF);

        $addedOrder = $this->client->queryPrivate('AddOrder', [
            'pair' => $this->tradingPair,
            'type' => 'buy',
            'ordertype' => 'market',
            'volume' => bcdiv((string) $amount, $this->getCurrentPrice(), 8),
            'oflags' => 'fciq', // prefer fee in quote currency
            'userref' => $this->lastUserRef,
        ]);

        $orderId = $addedOrder['txid'][array_key_first($addedOrder['txid'])];

        // check that its closed
        $this->checkIfOrderIsFilled($orderId);

        return $this->getCompletedBuyOrder($orderId);
    }

    public function checkIfOrderIsFilled(string $orderId): CompletedBuyOrder
    {
        $trades = $this->client->queryPrivate('OpenOrders', ['userref' => $this->lastUserRef]);
        if (\count($trades['open'] ?? []) > 0) {
            throw new PendingBuyOrderException($orderId);
        }

        return $this->getCompletedBuyOrder($orderId);
    }

    public function cancelBuyOrder(string $orderId): void
    {
        $this->client->queryPrivate('CancelOrder', [
            'txid' => $orderId,
        ]);
    }

    protected function getCurrentPrice(): string
    {
        $tickerInfo = $this->client->queryPublic('Ticker', [
            'pair' => $this->tradingPair,
        ]);

        return $tickerInfo[array_key_first($tickerInfo)]['a'][0];
    }

    protected function getCompletedBuyOrder(string $orderId): CompletedBuyOrder
    {
        $trades = $this->client->queryPrivate('TradesHistory', ['start' => time() - 900]);
        $orderInfo = null;

        foreach ($trades['trades'] ?? [] as $trade) {
            if ($trade['ordertxid'] === $orderId) {
                $orderInfo = $trade;

                break;
            }
        }

        if (null === $orderInfo) {
            throw new KrakenClientException('no open orders left yet order was not found, you should investigate this');
        }

        return (new CompletedBuyOrder())
            ->setAmountInSatoshis((int) bcmul($orderInfo['vol'], self::SATOSHIS_IN_A_BITCOIN, 8))
            ->setFeesInSatoshis(0)
            ->setDisplayAmountBought($orderInfo['vol'].' BTC')
            ->setDisplayAmountSpent($orderInfo['cost'].' '.$this->baseCurrency)
            ->setDisplayAveragePrice($orderInfo['price'].' '.$this->baseCurrency)
            ->setDisplayFeesSpent($orderInfo['fee'].' '.$this->baseCurrency)
        ;
    }
}

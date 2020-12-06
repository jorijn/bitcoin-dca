<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Bitcoin;
use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use Jorijn\Bitcoin\Dca\Exception\KrakenClientException;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyServiceInterface;

class KrakenBuyService implements BuyServiceInterface
{
    protected array $lastUserRefs = [];
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
        $lastUserRef = (string) random_int(0, 0x7FFFFFFF);

        $addedOrder = $this->client->queryPrivate('AddOrder', [
            'pair' => $this->tradingPair,
            'type' => 'buy',
            'ordertype' => 'market',
            'volume' => bcdiv((string) $amount, $this->getCurrentPrice(), Bitcoin::DECIMALS),
            'oflags' => 'fciq', // prefer fee in quote currency
            'userref' => $lastUserRef,
        ]);

        $orderId = $addedOrder['txid'][array_key_first($addedOrder['txid'])];

        $this->lastUserRefs[$orderId] = $lastUserRef;

        // check that its closed
        return $this->checkIfOrderIsFilled($orderId);
    }

    public function checkIfOrderIsFilled(string $orderId): CompletedBuyOrder
    {
        $trades = $this->client->queryPrivate('OpenOrders', ['userref' => $this->lastUserRefs[$orderId] ?? null]);
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
            ->setAmountInSatoshis((int) bcmul($orderInfo['vol'], Bitcoin::SATOSHIS, Bitcoin::DECIMALS))
            ->setFeesInSatoshis(0)
            ->setDisplayAmountBought($orderInfo['vol'].' BTC')
            ->setDisplayAmountSpent($orderInfo['cost'].' '.$this->baseCurrency)
            ->setDisplayAveragePrice($orderInfo['price'].' '.$this->baseCurrency)
            ->setDisplayFeesSpent($orderInfo['fee'].' '.$this->baseCurrency)
        ;
    }
}

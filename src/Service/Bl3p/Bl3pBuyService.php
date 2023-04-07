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

namespace Jorijn\Bitcoin\Dca\Service\Bl3p;

use Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface;
use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyServiceInterface;

class Bl3pBuyService implements BuyServiceInterface
{
    final public const ORDER_ID = 'order_id';
    final public const TOTAL_FEE = 'total_fee';
    final public const DATA = 'data';
    final public const TOTAL_AMOUNT = 'total_amount';
    final public const VALUE_INT = 'value_int';
    final public const CURRENCY = 'currency';
    final public const DISPLAY = 'display';
    final public const TOTAL_SPENT = 'total_spent';
    final public const DISPLAY_SHORT = 'display_short';
    final public const AVG_COST = 'avg_cost';
    final public const ORDER_STATUS_CLOSED = 'closed';
    final public const STATUS = 'status';
    final public const TYPE = 'type';
    final public const AMOUNT_FUNDS_INT = 'amount_funds_int';
    final public const FEE_CURRENCY = 'fee_currency';
    final public const BL3P = 'bl3p';
    protected string $tradingPair;

    public function __construct(protected Bl3pClientInterface $bl3pClient, protected string $baseCurrency)
    {
        $this->tradingPair = sprintf('BTC%s', $this->baseCurrency);
    }

    public function supportsExchange(string $exchange): bool
    {
        return self::BL3P === $exchange;
    }

    public function initiateBuy(int $amount): CompletedBuyOrder
    {
        $result = $this->bl3pClient->apiCall($this->tradingPair.'/money/order/add', [
            self::TYPE => 'bid',
            self::AMOUNT_FUNDS_INT => $amount * 100000,
            self::FEE_CURRENCY => 'BTC',
        ]);

        return $this->checkIfOrderIsFilled((string) $result[self::DATA][self::ORDER_ID]);
    }

    public function checkIfOrderIsFilled(string $orderId): CompletedBuyOrder
    {
        $orderInfo = $this->bl3pClient->apiCall($this->tradingPair.'/money/order/result', [
            self::ORDER_ID => $orderId,
        ]);

        if (self::ORDER_STATUS_CLOSED !== $orderInfo[self::DATA][self::STATUS]) {
            throw new PendingBuyOrderException($orderId);
        }

        return (new CompletedBuyOrder())
            ->setAmountInSatoshis((int) $orderInfo[self::DATA][self::TOTAL_AMOUNT][self::VALUE_INT])
            ->setFeesInSatoshis(
                'BTC' === $orderInfo[self::DATA][self::TOTAL_FEE][self::CURRENCY]
                    ? (int) $orderInfo[self::DATA][self::TOTAL_FEE][self::VALUE_INT]
                    : 0
            )
            ->setDisplayAmountBought($orderInfo[self::DATA][self::TOTAL_AMOUNT][self::DISPLAY])
            ->setDisplayAmountSpent($orderInfo[self::DATA][self::TOTAL_SPENT][self::DISPLAY_SHORT])
            ->setDisplayAmountSpentCurrency($this->baseCurrency)
            ->setDisplayAveragePrice($orderInfo[self::DATA][self::AVG_COST][self::DISPLAY_SHORT])
            ->setDisplayFeesSpent($orderInfo[self::DATA][self::TOTAL_FEE][self::DISPLAY])
        ;
    }

    public function cancelBuyOrder(string $orderId): void
    {
        $this->bl3pClient->apiCall(
            $this->tradingPair.'/money/order/cancel',
            [self::ORDER_ID => $orderId]
        );
    }
}

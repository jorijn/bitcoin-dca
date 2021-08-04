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

namespace Jorijn\Bitcoin\Dca\Service;

use Jorijn\Bitcoin\Dca\Exception\PendingBuyOrderException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;

interface BuyServiceInterface
{
    /**
     * Should return true or false depending on if this service will support provided exchange name.
     */
    public function supportsExchange(string $exchange): bool;

    /**
     * Method should buy $amount of $baseCurrency in BTC. Should only return a CompletedBuyOrder object when the
     * (market) order was filled. Should throw PendingBuyOrderException if it is not filled yet.
     *
     * @param int $amount the amount that should be bought
     *
     * @throws PendingBuyOrderException
     */
    public function initiateBuy(int $amount): CompletedBuyOrder;

    /**
     * Method should check if the given $orderId is filled already. Should only return a CompletedBuyOrder object when
     * the (market) order was filled. Should throw PendingBuyOrderException if it is not filled yet.
     *
     * @param string $orderId the order id of the order that is being checked
     *
     * @throws PendingBuyOrderException
     */
    public function checkIfOrderIsFilled(string $orderId): CompletedBuyOrder;

    /**
     * Method should cancel the order corresponding with this order id. Method will be called if the order was not
     * filled within set timeout.
     *
     * @param string $orderId the order id of the order that is being cancelled
     */
    public function cancelBuyOrder(string $orderId): void;
}

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

namespace Jorijn\Bitcoin\Dca\Exception;

class PendingBuyOrderException extends \Exception
{
    protected string $orderId;

    public function __construct(string $orderId)
    {
        parent::__construct(__CLASS__.' is supposed to be handled, something went wrong here.');

        $this->orderId = $orderId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}

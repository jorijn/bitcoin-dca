<?php

declare(strict_types=1);

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

<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Provider;

interface WithdrawAddressProviderInterface
{
    /**
     * Method should return a Bitcoin address for withdrawal.
     */
    public function provide(): string;
}

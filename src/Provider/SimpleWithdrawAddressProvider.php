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

namespace Jorijn\Bitcoin\Dca\Provider;

use Jorijn\Bitcoin\Dca\Validator\ValidationInterface;

class SimpleWithdrawAddressProvider implements WithdrawAddressProviderInterface
{
    public function __construct(protected ValidationInterface $validation, protected ?string $configuredAddress)
    {
    }

    public function provide(): string
    {
        $this->validation->validate($this->configuredAddress);

        return $this->configuredAddress;
    }
}

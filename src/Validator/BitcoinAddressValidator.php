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

namespace Jorijn\Bitcoin\Dca\Validator;

use BitWasp\Bitcoin\Address\AddressCreator;

class BitcoinAddressValidator implements ValidationInterface
{
    protected AddressCreator $addressCreator;

    public function __construct(AddressCreator $addressCreator)
    {
        $this->addressCreator = $addressCreator;
    }

    public function validate($input): void
    {
        if (empty($input)) {
            throw new BitcoinAddressValidatorException('Configured address cannot be empty');
        }

        try {
            $this->addressCreator->fromString($input);
        } catch (\Throwable $exception) {
            throw new BitcoinAddressValidatorException('Configured address failed validation', $exception->getCode(), $exception);
        }
    }
}

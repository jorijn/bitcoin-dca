<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Validator;

use BitWasp\Bitcoin\Address\AddressCreator;

class BitcoinAddressValidator implements ValidationInterface
{
    /** @var AddressCreator */
    protected AddressCreator $addressCreator;

    public function __construct(AddressCreator $addressCreator)
    {
        $this->addressCreator = $addressCreator;
    }

    public function validate($input): bool
    {
        if (empty($input)) {
            throw new BitcoinAddressValidatorException('Configured address cannot be empty');
        }

        try {
            $this->addressCreator->fromString($input);

            return true;
        } catch (\Throwable $exception) {
            throw new BitcoinAddressValidatorException('Configured address failed validation', $exception->getCode(), $exception);
        }
    }
}

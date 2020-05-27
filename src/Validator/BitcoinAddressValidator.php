<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Validator;

use BitWasp\Bitcoin\Address\AddressCreator;

class BitcoinAddressValidator implements ValidationInterface
{
    /** @var AddressCreator */
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

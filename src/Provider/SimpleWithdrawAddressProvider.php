<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Provider;

use Jorijn\Bl3pDca\Validator\ValidationInterface;

class SimpleWithdrawAddressProvider implements WithdrawAddressProviderInterface
{
    protected ?string $configuredAddress;
    protected ValidationInterface $validation;

    public function __construct(ValidationInterface $validation, ?string $configuredAddress)
    {
        $this->configuredAddress = $configuredAddress;
        $this->validation = $validation;
    }

    public function provide(): string
    {
        $this->validation->validate($this->configuredAddress);

        return $this->configuredAddress;
    }
}

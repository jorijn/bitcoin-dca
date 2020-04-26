<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Provider;

use Jorijn\Bl3pDca\Validator\ValidationInterface;

class SimpleWithdrawAddressProvider implements WithdrawAddressProviderInterface
{
    protected string $configuredAddress;
    /** @var ValidationInterface */
    protected ValidationInterface $validation;

    public function __construct(ValidationInterface $validation, string $configuredAddress)
    {
        $this->configuredAddress = $configuredAddress;
        $this->validation = $validation;
    }

    public function provide(): string
    {
        if (!$this->validation->validate($this->configuredAddress)) {
            throw new \RuntimeException('Could not determine address to withdraw to, configured address does not validate');
        }

        return $this->configuredAddress;
    }
}

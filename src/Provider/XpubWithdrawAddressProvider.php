<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Provider;

use Jorijn\Bl3pDca\Factory\AddressFromMasterPublicKeyFactory;
use Jorijn\Bl3pDca\Validator\ValidationInterface;

class XpubWithdrawAddressProvider implements WithdrawAddressProviderInterface
{
    protected ValidationInterface $validation;
    protected AddressFromMasterPublicKeyFactory $keyFactory;
    protected ?string $configuredXPub;

    public function __construct(
        ValidationInterface $validation,
        AddressFromMasterPublicKeyFactory $keyFactory,
        ?string $configuredXPub
    ) {
        $this->validation = $validation;
        $this->keyFactory = $keyFactory;
        $this->configuredXPub = $configuredXPub;
    }

    public function provide(): string
    {
        $address = $this->keyFactory->derive($this->configuredXPub, '0/0');
        $this->validation->validate($address);

        return $address;
    }
}

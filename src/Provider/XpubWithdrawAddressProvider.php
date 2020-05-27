<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Provider;

use Jorijn\Bitcoin\Dca\Factory\AddressFromMasterPublicKeyFactory;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Jorijn\Bitcoin\Dca\Validator\ValidationInterface;

class XpubWithdrawAddressProvider implements WithdrawAddressProviderInterface
{
    protected ValidationInterface $validation;
    protected AddressFromMasterPublicKeyFactory $keyFactory;
    protected TaggedIntegerRepositoryInterface $xpubRepository;
    protected ?string $configuredXPub;

    public function __construct(
        ValidationInterface $validation,
        AddressFromMasterPublicKeyFactory $keyFactory,
        TaggedIntegerRepositoryInterface $xpubRepository,
        ?string $configuredXPub
    ) {
        $this->validation = $validation;
        $this->keyFactory = $keyFactory;
        $this->configuredXPub = $configuredXPub;
        $this->xpubRepository = $xpubRepository;
    }

    public function provide(): string
    {
        $activeIndex = $this->xpubRepository->get($this->configuredXPub);
        $activeDerivationPath = sprintf('0/%d', $activeIndex);
        $derivedAddress = $this->keyFactory->derive($this->configuredXPub, $activeDerivationPath);

        $this->validation->validate($derivedAddress);

        return $derivedAddress;
    }
}

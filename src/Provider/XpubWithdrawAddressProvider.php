<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Provider;

use Jorijn\Bl3pDca\Factory\AddressFromMasterPublicKeyFactory;
use Jorijn\Bl3pDca\Repository\TaggedIntegerRepositoryInterface;
use Jorijn\Bl3pDca\Validator\ValidationInterface;

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

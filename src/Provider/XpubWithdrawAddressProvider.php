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

use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponentInterface;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Jorijn\Bitcoin\Dca\Validator\ValidationInterface;

class XpubWithdrawAddressProvider implements WithdrawAddressProviderInterface
{
    public function __construct(
        protected ValidationInterface $validation,
        protected AddressFromMasterPublicKeyComponentInterface $addressFromMasterPublicKeyComponent,
        protected TaggedIntegerRepositoryInterface $taggedIntegerRepository,
        protected ?string $configuredXPub
    ) {
    }

    public function provide(): string
    {
        $activeIndex = $this->taggedIntegerRepository->get($this->configuredXPub);
        $activeDerivationPath = sprintf('0/%d', $activeIndex);
        $derivedAddress = $this->addressFromMasterPublicKeyComponent->derive(
            $this->configuredXPub,
            $activeDerivationPath
        );

        $this->validation->validate($derivedAddress);

        return $derivedAddress;
    }
}

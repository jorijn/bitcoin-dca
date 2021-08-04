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

namespace Jorijn\Bitcoin\Dca\Factory;

use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponentInterface;
use Jorijn\Bitcoin\Dca\Exception\NoDerivationComponentAvailableException;

class DeriveFromMasterPublicKeyComponentFactory
{
    /**
     * @var AddressFromMasterPublicKeyComponentInterface[]|iterable
     */
    protected iterable $availableComponents;

    public function __construct(iterable $availableComponents)
    {
        $this->availableComponents = $availableComponents;
    }

    public function createDerivationComponent(): AddressFromMasterPublicKeyComponentInterface
    {
        foreach ($this->availableComponents as $availableComponent) {
            if (true === $availableComponent->supported()) {
                return $availableComponent;
            }
        }

        throw new NoDerivationComponentAvailableException('no derivation component is available');
    }
}

<?php

declare(strict_types=1);

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

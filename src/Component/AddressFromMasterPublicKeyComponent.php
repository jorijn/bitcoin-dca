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

namespace Jorijn\Bitcoin\Dca\Component;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\GlobalPrefixConfig;
use BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig;
use BitWasp\Bitcoin\Key\Deterministic\Slip132\Slip132;
use BitWasp\Bitcoin\Key\KeyToScript\KeyToScriptHelper;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Network\Slip132\BitcoinRegistry;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\Base58ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use Jorijn\Bitcoin\Dca\Exception\NoMasterPublicKeyAvailableException;

class AddressFromMasterPublicKeyComponent implements AddressFromMasterPublicKeyComponentInterface
{
    public function derive(string $masterPublicKey, $path = '0/0'): string
    {
        if (empty($masterPublicKey)) {
            throw new \InvalidArgumentException('Master Public Key cannot be empty');
        }

        $ecAdapter = Bitcoin::getEcAdapter();
        $network = NetworkFactory::bitcoin();
        $slip132 = new Slip132(new KeyToScriptHelper($ecAdapter));
        $bitcoinRegistry = new BitcoinRegistry();

        switch ($masterPublicKey[0] ?? null) {
            case 'x':
                $pubPrefix = $slip132->p2pkh($bitcoinRegistry);
                $pub = $masterPublicKey;

                break;

            case 'y':
                $pubPrefix = $slip132->p2shP2wpkh($bitcoinRegistry);
                $pub = $masterPublicKey;

                break;

            case 'z':
                $pubPrefix = $slip132->p2wpkh($bitcoinRegistry);
                $pub = $masterPublicKey;

                break;

            default:
                throw new NoMasterPublicKeyAvailableException('no master public key available');
        }

        $base58ExtendedKeySerializer = new Base58ExtendedKeySerializer(
            new ExtendedKeySerializer(
                $ecAdapter,
                new GlobalPrefixConfig([
                    new NetworkConfig($network, [
                        $pubPrefix,
                    ]),
                ])
            )
        );

        $key = $base58ExtendedKeySerializer->parse($network, $pub);
        $hierarchicalKey = $key->derivePath($path);

        return $hierarchicalKey->getAddress(new AddressCreator())->getAddress();
    }

    public function supported(): bool
    {
        // this component only works on PHP 64-bits
        return \PHP_INT_SIZE === 8;
    }
}

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

use Psr\Log\LoggerInterface;

class ExternalAddressFromMasterPublicKeyComponent implements AddressFromMasterPublicKeyComponentInterface
{
    protected array $addressCache = [];
    protected string $externalToolLocation;
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, string $externalToolLocation)
    {
        $this->externalToolLocation = $externalToolLocation;
        $this->logger = $logger;
    }

    public function derive(string $masterPublicKey, $path = '0/0'): string
    {
        if (empty($masterPublicKey)) {
            throw new \InvalidArgumentException('Master Public Key cannot be empty');
        }

        // if item is present in the cache, return it from there
        if (isset($this->addressCache[$masterPublicKey][$path])) {
            return $this->addressCache[$masterPublicKey][$path];
        }

        [$namespace, $index] = explode('/', $path);
        if ('0' !== $namespace) {
            throw new \InvalidArgumentException('no change addresses supported');
        }

        $rangeLow = ($index - 25) < 0 ? 0 : $index - 25;
        $command = sprintf(
            '%s derive %s %s %s 2>&1',
            $this->externalToolLocation,
            escapeshellarg($masterPublicKey),
            escapeshellarg((string) $rangeLow),
            escapeshellarg((string) ($index + 25))
        );

        $strResult = shell_exec($command);

        try {
            // decode the result and add it to the cache in the same go.
            $result = $this->addressCache[$masterPublicKey] = json_decode($strResult, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            $this->logger->error(
                'failed to decode from external derivation tool: '.($exception->getMessage() ?: \get_class($exception)),
                [
                    'exception' => $exception,
                    'result' => $strResult,
                ]
            );

            throw $exception;
        }

        return $result[$path];
    }

    public function supported(): bool
    {
        return true;
    }
}

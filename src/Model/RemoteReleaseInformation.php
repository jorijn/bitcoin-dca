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

namespace Jorijn\Bitcoin\Dca\Model;

class RemoteReleaseInformation
{
    protected array $releaseInformation;

    public function __construct(
        array $releaseInformation,
        protected string $localVersion,
        protected string $remoteVersion
    ) {
        $this->releaseInformation = $releaseInformation;
    }

    public function getReleaseInformation(): array
    {
        return $this->releaseInformation;
    }

    public function getLocalVersion(): string
    {
        return $this->localVersion;
    }

    public function getRemoteVersion(): string
    {
        return $this->remoteVersion;
    }

    public function isOutdated(): bool
    {
        return version_compare(
            $this->getLocalVersion(),
            $this->getRemoteVersion(),
            '<'
        );
    }
}

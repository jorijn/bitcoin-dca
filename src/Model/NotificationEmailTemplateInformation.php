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

class NotificationEmailTemplateInformation
{
    public function __construct(
        protected string $exchange,
        protected string $logoLocation,
        protected string $iconLocation,
        protected string $quotesLocation
    ) {
    }

    public function getExchange(): string
    {
        return $this->exchange;
    }

    public function getLogoLocation(): string
    {
        return $this->logoLocation;
    }

    public function getIconLocation(): string
    {
        return $this->iconLocation;
    }

    public function getQuotesLocation(): string
    {
        return $this->quotesLocation;
    }
}

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

namespace Jorijn\Bitcoin\Dca\Repository;

interface TaggedIntegerRepositoryInterface
{
    public function increase(string $tag, int $value = 1): void;

    public function decrease(string $tag, int $value = 1): void;

    public function set(string $tag, int $value): void;

    public function get(string $tag): int;
}

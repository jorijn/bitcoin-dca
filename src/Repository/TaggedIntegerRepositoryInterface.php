<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Repository;

interface TaggedIntegerRepositoryInterface
{
    public function increase(string $tag, int $value = 1): void;

    public function decrease(string $tag, int $value = 1): void;

    public function set(string $tag, int $value): void;

    public function get(string $tag): int;
}

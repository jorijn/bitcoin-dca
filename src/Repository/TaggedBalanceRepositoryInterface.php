<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Repository;

interface TaggedBalanceRepositoryInterface
{
    public function increaseTagBalance(string $tag, int $satoshis): void;

    public function decreaseTagBalance(string $tag, int $satoshis): void;

    public function setTagBalance(string $tag, int $satoshis): void;

    public function getTagBalance(string $tag): int;
}

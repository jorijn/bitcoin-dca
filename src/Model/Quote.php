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

class Quote
{
    protected string $quote;
    protected string $author;

    public function __construct(string $quote, string $author)
    {
        $this->quote = $quote;
        $this->author = $author;
    }

    public function getQuote(): string
    {
        return $this->quote;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }
}

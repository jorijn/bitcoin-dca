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

namespace Tests\Jorijn\Bitcoin\Dca\Model;

use Jorijn\Bitcoin\Dca\Model\Quote;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Model\Quote
 *
 * @internal
 */
final class QuoteTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getAuthor
     * @covers ::getQuote
     */
    public function testGetters(): void
    {
        $dto = new Quote(
            $quote = 'q'.random_int(1000, 2000),
            $author = 'a'.random_int(1000, 2000)
        );

        static::assertSame($quote, $dto->getQuote());
        static::assertSame($author, $dto->getAuthor());
    }
}

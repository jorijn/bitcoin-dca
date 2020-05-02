<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Command;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Command\WithdrawCommand
 *
 * @internal
 */
final class WithdrawCommandTest extends TestCase
{
    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'rb+', false);
        fwrite($stream, $input);
        rewind($stream);

        return $stream;
    }
}

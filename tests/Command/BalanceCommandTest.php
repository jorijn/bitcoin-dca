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

namespace Tests\Jorijn\Bitcoin\Dca\Command;

use Jorijn\Bitcoin\Dca\Command\BalanceCommand;
use Jorijn\Bitcoin\Dca\Service\BalanceService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Command\BalanceCommand
 *
 * @covers ::__construct
 * @covers ::configure
 *
 * @internal
 */
final class BalanceCommandTest extends TestCase
{
    private \Jorijn\Bitcoin\Dca\Service\BalanceService|\PHPUnit\Framework\MockObject\MockObject $service;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(BalanceService::class);
        $this->commandTester = new CommandTester(new BalanceCommand($this->service));
    }

    /**
     * @covers ::execute
     */
    public function testFailure(): void
    {
        $errorMessage = 'message'.random_int(1000, 2000);
        $exception = new \Exception($errorMessage);

        $this->service
            ->expects(static::once())
            ->method('getBalances')
            ->willThrowException($exception)
        ;

        $this->commandTester->execute([]);

        static::assertStringContainsString('ERROR', $this->commandTester->getDisplay());
        static::assertStringContainsString($errorMessage, $this->commandTester->getDisplay());
        static::assertSame(1, $this->commandTester->getStatusCode());
    }

    /**
     * @covers ::execute
     */
    public function testDisplaysBalanceFromApi(): void
    {
        $btcBalance = random_int(1000, 2000);
        $euroBalance = random_int(1000, 2000);

        $this->service
            ->expects(static::once())
            ->method('getBalances')
            ->willReturn(
                [
                    ['BTC', $btcBalance, $btcBalance],
                    ['EUR', $euroBalance, $euroBalance],
                ]
            )
        ;

        $this->commandTester->execute([]);

        static::assertStringContainsString((string) $btcBalance, $this->commandTester->getDisplay());
        static::assertStringContainsString((string) $euroBalance, $this->commandTester->getDisplay());
        static::assertSame(0, $this->commandTester->getStatusCode());
    }
}

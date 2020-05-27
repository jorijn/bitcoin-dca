<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Command;

use Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface;
use Jorijn\Bitcoin\Dca\Command\BalanceCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Command\BalanceCommand
 * @covers ::__construct
 * @covers ::configure
 *
 * @internal
 */
final class BalanceCommandTest extends TestCase
{
    /** @var Bl3pClientInterface|MockObject */
    private $client;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Bl3pClientInterface::class);
        $this->commandTester = new CommandTester(new BalanceCommand($this->client));
    }

    /**
     * @covers ::execute
     */
    public function testApiFailure(): void
    {
        $errorMessage = 'message'.random_int(1000, 2000);
        $apiException = new \Exception($errorMessage);

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('GENMKT/money/info')
            ->willThrowException($apiException)
        ;

        $this->commandTester->execute([]);

        static::assertStringContainsString('API failure:', $this->commandTester->getDisplay());
        static::assertStringContainsString($errorMessage, $this->commandTester->getDisplay());
    }

    /**
     * @covers ::execute
     */
    public function testDisplaysBalanceFromApi(): void
    {
        $btcBalance = random_int(1000, 2000);
        $euroBalance = random_int(1000, 2000);

        $this->client
            ->expects(static::once())
            ->method('apiCall')
            ->with('GENMKT/money/info')
            ->willReturn(
                [
                    'data' => [
                        'wallets' => [
                            'BTC' => [
                                'balance' => [
                                    'display' => $btcBalance,
                                ],
                                'available' => [
                                    'display' => $btcBalance,
                                ],
                            ],
                            'EUR' => [
                                'balance' => [
                                    'display' => $euroBalance,
                                ],
                                'available' => [
                                    'display' => $euroBalance,
                                ],
                            ],
                        ],
                    ],
                ],
            )
        ;

        $this->commandTester->execute([]);

        static::assertStringContainsString((string) $btcBalance, $this->commandTester->getDisplay());
        static::assertStringContainsString((string) $euroBalance, $this->commandTester->getDisplay());
    }
}

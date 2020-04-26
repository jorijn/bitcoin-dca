<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Command;

use Jorijn\Bl3pDca\Client\Bl3pClientInterface;
use Jorijn\Bl3pDca\Command\BalanceCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Command\BalanceCommand
 * @covers ::__construct
 * @covers ::configure
 */
class BalanceCommandTest extends TestCase
{
    /** @var Bl3pClientInterface|MockObject */
    private $client;
    private CommandTester $commandTester;

    /**
     * @covers ::execute
     */
    public function testApiFailure(): void
    {
        $errorMessage = 'message'.mt_rand();
        $apiException = new \Exception($errorMessage);

        $this->client
            ->expects(self::once())
            ->method('apiCall')
            ->with('GENMKT/money/info')
            ->willThrowException($apiException);

        $this->commandTester->execute([]);

        self::assertStringContainsString('API failure:', $this->commandTester->getDisplay());
        self::assertStringContainsString($errorMessage, $this->commandTester->getDisplay());
    }

    /**
     * @covers ::execute
     */
    public function testDisplaysBalanceFromApi(): void
    {
        $btcBalance = mt_rand();
        $euroBalance = mt_rand();

        $this->client
            ->expects(self::once())
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
            );

        $this->commandTester->execute([]);

        self::assertStringContainsString((string) $btcBalance, $this->commandTester->getDisplay());
        self::assertStringContainsString((string) $euroBalance, $this->commandTester->getDisplay());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Bl3pClientInterface::class);
        $this->commandTester = new CommandTester(new BalanceCommand('balance', $this->client));
    }
}

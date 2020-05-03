<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Command;

use Jorijn\Bl3pDca\Command\BuyCommand;
use Jorijn\Bl3pDca\Exception\BuyTimeoutException;
use Jorijn\Bl3pDca\Model\CompletedBuyOrder;
use Jorijn\Bl3pDca\Service\BuyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Command\BuyCommand
 * @covers ::__construct
 * @covers ::configure
 *
 * @internal
 */
final class BuyCommandTest extends TestCase
{
    /** @var BuyService|MockObject */
    private $buyService;
    /** @var BuyCommand */
    private BuyCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyService = $this->createMock(BuyService::class);
        $this->command = new BuyCommand($this->buyService);
    }

    public function providerOfTags(): array
    {
        return [
            'with tag' => ['tag'.mt_rand()],
            'without tag' => [],
        ];
    }

    /**
     * @covers ::execute
     */
    public function testAmountIsNotNumeric(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['command' => $this->command->getName(), 'amount' => 'string'.mt_rand()]);

        static::assertStringContainsString('Amount should be numeric, e.g. 10', $commandTester->getDisplay(true));
        static::assertSame(1, $commandTester->getStatusCode());
    }

    /**
     * @covers ::execute
     */
    public function testNotUnattendedAndNotConfirming(): void
    {
        $amount = mt_rand();

        // not buying
        $this->buyService->expects(static::never())->method('buy');

        $commandTester = $this->createCommandTester();
        $commandTester->setInputs(['no']);
        $commandTester->execute(['command' => $this->command->getName(), 'amount' => $amount]);

        static::assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @covers ::execute
     * @dataProvider providerOfTags
     */
    public function testNotUnattendedAndConfirmsBuy(string $tag = null): void
    {
        $amount = mt_rand();

        $orderInformation = (new CompletedBuyOrder())
            ->setDisplayAmountBought(mt_rand().' BTC')
            ->setDisplayAmountSpent(mt_rand().' EUR')
            ->setDisplayAveragePrice(mt_rand().' EUR')
            ->setDisplayFeesSpent('0.'.mt_rand().' BTC')
        ;

        $invocationMocker = $this->buyService
            ->expects(static::once())
            ->method('buy')
            ->willReturn($orderInformation)
        ;

        if (!empty($tag)) {
            $invocationMocker->with($amount, $tag);
        } else {
            $invocationMocker->with($amount);
        }

        $commandTester = $this->createCommandTester();
        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'command' => $this->command->getName(),
            'amount' => $amount,
        ] + (!empty($tag) ? ['--tag' => $tag] : []));

        static::assertSame(0, $commandTester->getStatusCode());
        static::assertStringContainsString(sprintf(
            '[OK] Bought: %s, EUR: %s, price: %s, spent fees: %s',
            $orderInformation->getDisplayAmountBought(),
            $orderInformation->getDisplayAmountSpent(),
            $orderInformation->getDisplayAveragePrice(),
            $orderInformation->getDisplayFeesSpent(),
        ), $commandTester->getDisplay(true));
    }

    public function testUnattendedBuy(string $tag = null): void
    {
        $amount = mt_rand();

        $orderInformation = (new CompletedBuyOrder())
            ->setDisplayAmountBought(mt_rand().' BTC')
            ->setDisplayAmountSpent(mt_rand().' EUR')
            ->setDisplayAveragePrice(mt_rand().' EUR')
            ->setDisplayFeesSpent('0.'.mt_rand().' BTC')
        ;

        $invocationMocker = $this->buyService
            ->expects(static::once())
            ->method('buy')
            ->willReturn($orderInformation)
        ;

        if (!empty($tag)) {
            $invocationMocker->with($amount, $tag);
        } else {
            $invocationMocker->with($amount);
        }

        $commandTester = $this->createCommandTester();
        $commandTester->execute([
            'command' => $this->command->getName(),
            'amount' => $amount,
            '--yes' => null,
        ] + (!empty($tag) ? ['--tag' => $tag] : []));

        static::assertSame(0, $commandTester->getStatusCode());
        static::assertStringContainsString(sprintf(
            '[OK] Bought: %s, EUR: %s, price: %s, spent fees: %s',
            $orderInformation->getDisplayAmountBought(),
            $orderInformation->getDisplayAmountSpent(),
            $orderInformation->getDisplayAveragePrice(),
            $orderInformation->getDisplayFeesSpent(),
        ), $commandTester->getDisplay(true));
    }

    /**
     * @covers ::execute
     */
    public function testBuyingFailsExceptionIsHandled(): void
    {
        $amount = mt_rand();
        $exception = new BuyTimeoutException('error'.mt_rand());

        $this->buyService
            ->expects(static::once())
            ->method('buy')
            ->with($amount)
            ->willThrowException($exception)
        ;

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['command' => $this->command->getName(), 'amount' => $amount, '--yes' => null]);

        static::assertSame(1, $commandTester->getStatusCode());
        static::assertStringContainsString('[ERROR] '.$exception->getMessage(), $commandTester->getDisplay(true));
    }

    protected function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add($this->command->setName('buy'));

        return new CommandTester($this->command);
    }
}

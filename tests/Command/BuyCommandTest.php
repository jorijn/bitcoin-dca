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

use Exception;
use Jorijn\Bitcoin\Dca\Command\BuyCommand;
use Jorijn\Bitcoin\Dca\Exception\BuyTimeoutException;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Command\BuyCommand
 * @covers ::__construct
 * @covers ::configure
 *
 * @internal
 */
final class BuyCommandTest extends TestCase
{
    public const AMOUNT = 'amount';
    public const COMMAND = 'command';

    /** @var BuyService|MockObject */
    private $buyService;

    /** @var MockObject|SerializerInterface */
    private $serializer;

    private BuyCommand $command;
    private string $baseCurrency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseCurrency = 'BC'.random_int(1, 9);
        $this->buyService = $this->createMock(BuyService::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->command = new BuyCommand($this->buyService, $this->serializer, $this->baseCurrency);
    }

    public function providerOfDifferentFormats(): array
    {
        return [
            ['yaml'],
            ['json'],
            ['xml'],
            ['csv'],
        ];
    }

    public function providerOfTags(): array
    {
        return [
            'with tag' => ['tag'.random_int(1000, 2000)],
            'without tag' => [],
        ];
    }

    /**
     * @covers ::execute
     */
    public function testAmountIsNotNumeric(): void
    {
        $commandTester = $this->createCommandTester();
        $commandTester->execute(
            [
                self::COMMAND => $this->command->getName(),
                self::AMOUNT => 'string'.random_int(1000, 2000),
            ]
        );

        static::assertStringContainsString('Amount should be numeric, e.g. 10', $commandTester->getDisplay(true));
        static::assertSame(1, $commandTester->getStatusCode());
    }

    /**
     * @covers ::execute
     */
    public function testNotUnattendedAndNotConfirming(): void
    {
        $amount = random_int(1000, 2000);

        // not buying
        $this->buyService->expects(static::never())->method('buy');

        $commandTester = $this->createCommandTester();
        $commandTester->setInputs(['no']);
        $commandTester->execute([self::COMMAND => $this->command->getName(), self::AMOUNT => $amount]);

        static::assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @covers ::displayFormattedPurchaseOrder
     * @covers ::execute
     * @covers ::isDisplayingMachineReadableOutput
     * @dataProvider providerOfTags
     */
    public function testNotUnattendedAndConfirmsBuy(string $tag = null): void
    {
        [$amount, $orderInformation] = $this->prepareBuyTest($tag);

        $commandTester = $this->createCommandTester();
        $commandTester->setInputs(['yes']);
        $commandTester->execute(
            [
                self::COMMAND => $this->command->getName(),
                self::AMOUNT => $amount,
            ] + (empty($tag) ? [] : ['--tag' => $tag])
        );

        static::assertSame(0, $commandTester->getStatusCode());
        static::assertStringContainsString(
            sprintf(
                '[OK] Bought: %s, %s: %s, price: %s, spent fees: %s',
                $orderInformation->getDisplayAmountBought(),
                $this->baseCurrency,
                $orderInformation->getDisplayAmountSpent(),
                $orderInformation->getDisplayAveragePrice(),
                $orderInformation->getDisplayFeesSpent(),
            ),
            $commandTester->getDisplay(true)
        );
    }

    /**
     * @covers ::displayFormattedPurchaseOrder
     * @covers ::execute
     */
    public function testUnattendedBuy(string $tag = null): void
    {
        [$amount, $orderInformation] = $this->prepareBuyTest($tag);

        $commandTester = $this->createCommandTester();
        $commandTester->execute(
            [
                self::COMMAND => $this->command->getName(),
                self::AMOUNT => $amount,
                '--yes' => null,
            ] + (empty($tag) ? [] : ['--tag' => $tag])
        );

        static::assertSame(0, $commandTester->getStatusCode());
        static::assertStringContainsString(
            sprintf(
                '[OK] Bought: %s, %s: %s, price: %s, spent fees: %s',
                $orderInformation->getDisplayAmountBought(),
                $this->baseCurrency,
                $orderInformation->getDisplayAmountSpent(),
                $orderInformation->getDisplayAveragePrice(),
                $orderInformation->getDisplayFeesSpent(),
            ),
            $commandTester->getDisplay(true)
        );
    }

    /**
     * @covers ::displayFormattedPurchaseOrder
     * @covers ::execute
     * @covers ::isDisplayingMachineReadableOutput
     * @dataProvider providerOfDifferentFormats
     */
    public function testUnattendedBuyWithAlternativeOutputFormat(string $requestedFormat): void
    {
        [$amount, $orderInformation] = $this->prepareBuyTest(null);

        $mockedSerializerOutput = 'output_'.random_int(1000, 2000);

        $this->serializer
            ->expects(static::once())
            ->method('serialize')
            ->with($orderInformation, $requestedFormat)
            ->willReturn($mockedSerializerOutput)
        ;

        $commandTester = $this->createCommandTester();
        $commandTester->execute(
            [
                self::COMMAND => $this->command->getName(),
                self::AMOUNT => $amount,
                '--yes' => null,
                '--output' => $requestedFormat,
            ]
        );

        static::assertStringContainsString(
            $mockedSerializerOutput,
            $commandTester->getDisplay(true)
        );
        static::assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @covers ::execute
     */
    public function testBuyingFailsExceptionIsHandled(): void
    {
        $amount = random_int(1000, 2000);
        $buyTimeoutException = new BuyTimeoutException('error'.random_int(1000, 2000));

        $this->buyService
            ->expects(static::once())
            ->method('buy')
            ->with($amount)
            ->willThrowException($buyTimeoutException)
        ;

        $commandTester = $this->createCommandTester();
        $commandTester->execute([self::COMMAND => $this->command->getName(), self::AMOUNT => $amount, '--yes' => null]);

        static::assertSame(1, $commandTester->getStatusCode());
        static::assertStringContainsString(
            '[ERROR] '.$buyTimeoutException->getMessage(),
            $commandTester->getDisplay(true)
        );
    }

    protected function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add($this->command->setName('buy'));

        return new CommandTester($this->command);
    }

    /**
     * @throws Exception
     */
    protected function prepareBuyTest(?string $tag): array
    {
        $amount = random_int(1000, 2000);

        $completedBuyOrder = (new CompletedBuyOrder())
            ->setDisplayAmountBought(random_int(1000, 2000).' BTC')
            ->setDisplayAmountSpent(random_int(1000, 2000).' EUR')
            ->setDisplayAmountSpentCurrency('EUR')
            ->setDisplayAveragePrice(random_int(1000, 2000).' EUR')
            ->setDisplayFeesSpent('0.'.random_int(1000, 2000).' BTC')
        ;

        $invocationMocker = $this->buyService
            ->expects(static::once())
            ->method('buy')
            ->willReturn($completedBuyOrder)
        ;

        if (!empty($tag)) {
            $invocationMocker->with($amount, $tag);
        } else {
            $invocationMocker->with($amount);
        }

        return [$amount, $completedBuyOrder];
    }
}

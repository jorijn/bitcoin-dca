<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Command;

use Jorijn\Bitcoin\Dca\Command\VerifyXPubCommand;
use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponent;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Command\VerifyXPubCommand
 * @covers ::__construct
 * @covers ::configure
 *
 * @internal
 */
final class VerifyXPubCommandTest extends TestCase
{
    /** @var AddressFromMasterPublicKeyComponent|MockObject */
    private $keyFactory;
    /** @var MockObject|TaggedIntegerRepositoryInterface */
    private $xpubRepository;
    private string $configuredKey;
    private string $environmentKey;
    private VerifyXPubCommand $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyFactory = $this->createMock(AddressFromMasterPublicKeyComponent::class);
        $this->xpubRepository = $this->createMock(TaggedIntegerRepositoryInterface::class);
        $this->configuredKey = 'xpub'.random_int(1000, 2000);
        $this->environmentKey = 'CONFIG_KEY_HERE';
        $this->command = new VerifyXPubCommand(
            $this->keyFactory,
            $this->xpubRepository,
            $this->configuredKey,
            $this->environmentKey
        );
    }

    /**
     * @covers ::execute
     */
    public function testNoXpubWasProvided(): void
    {
        $this->command = new VerifyXPubCommand(
            $this->keyFactory,
            $this->xpubRepository,
            '',
            $this->environmentKey
        );

        $this->xpubRepository->expects(static::never())->method('get');
        $this->keyFactory->expects(static::never())->method('derive');

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['command' => $this->command->getName()]);

        static::assertStringContainsString(
            '[ERROR] Unable to find any configured X/Z/Y-pub.',
            $commandTester->getDisplay()
        );
        static::assertSame(1, $commandTester->getStatusCode());
    }

    /**
     * @covers ::execute
     */
    public function testDisplaysRelevantWithdrawalAddresses(): void
    {
        $derivedAddresses = [];
        $activeIndex = random_int(0, 10);

        $this->xpubRepository
            ->expects(static::once())
            ->method('get')
            ->with($this->configuredKey)
            ->willReturn($activeIndex)
        ;

        $this->keyFactory
            ->expects(static::atLeastOnce())
            ->method('derive')
            ->with(
                $this->configuredKey,
                static::callback(fn (string $derivation) => preg_match(
                    '/^0\/(\d+)$/',
                    $derivation,
                    $matches
                ) && $matches[0] ?? null > 0)
            )
            ->willReturnCallback(function (string $configuredKey, string $derivation) use (&$derivedAddresses) {
                self::assertSame($configuredKey, $this->configuredKey);
                preg_match('/^0\/(\d+)$/', $derivation, $matches);

                return $derivedAddresses[$matches[0]] = sprintf('address_%s', $matches[0]);
            })
        ;

        $commandTester = $this->createCommandTester();
        $commandTester->execute(['command' => $this->command->getName()]);
        $output = $commandTester->getDisplay(true);

        // test each derived address is printed back to the user
        foreach ($derivedAddresses as $derivedAddress) {
            static::assertStringContainsString($derivedAddress, $output);
        }

        // test the active derivation is marked with a "<"
        static::assertMatchesRegularExpression('/0\/'.$activeIndex.'.+</', $output);
    }

    protected function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add($this->command->setName('verify-xpub'));

        return new CommandTester($this->command);
    }
}

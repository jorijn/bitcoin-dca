<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Command;

use Jorijn\Bitcoin\Dca\Command\VersionCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Command\VersionCommand
 *
 * @internal
 */
final class VersionCommandTest extends TestCase
{
    protected VersionCommand $command;
    protected string $versionFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->versionFile = tempnam(sys_get_temp_dir(), 'version');
        $this->command = new VersionCommand($this->versionFile);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->versionFile)) {
            unlink($this->versionFile);
        }
    }

    /**
     * @covers ::execute
     */
    public function testVersionIsParsed(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        file_put_contents($this->versionFile, json_encode($data, JSON_THROW_ON_ERROR));

        $commandTester = $this->createCommandTester();
        $commandTester->execute([
            'command' => $this->command->getName(),
        ]);

        $display = $commandTester->getDisplay(true);
        static::assertStringContainsString('key1', $display);
        static::assertStringContainsString('key2', $display);
        static::assertStringContainsString('value1', $display);
        static::assertStringContainsString('value2', $display);
        static::assertSame(0, $commandTester->getStatusCode());
    }

    /**
     * @covers ::execute
     */
    public function testVersionNotPresent(): void
    {
        unlink($this->versionFile);

        $commandTester = $this->createCommandTester();
        $commandTester->execute([
            'command' => $this->command->getName(),
        ]);

        $display = $commandTester->getDisplay(true);
        static::assertStringContainsString('version', $display);
        static::assertStringContainsString('no version file present', $display);
        static::assertSame(0, $commandTester->getStatusCode());
    }

    protected function createCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add($this->command->setName('version'));

        return new CommandTester($this->command);
    }
}

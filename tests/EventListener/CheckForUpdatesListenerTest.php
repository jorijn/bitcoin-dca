<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\EventListener;

use Exception;
use Jorijn\Bitcoin\Dca\Command\BuyCommand;
use Jorijn\Bitcoin\Dca\EventListener\CheckForUpdatesListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\CheckForUpdatesListener
 * @covers ::___construct
 *
 * @internal
 */
final class CheckForUpdatesListenerTest extends TestCase
{
    /** @var InputInterface|MockObject */
    private $input;
    /** @var BufferedOutput */
    private $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->input = $this->createMock(InputInterface::class);
        $this->output = new BufferedOutput();
    }

    /**
     * @covers ::onConsoleTerminated
     */
    public function testVersionCheckIsDisabledThroughConfiguration(): void
    {
        $listener = new CheckForUpdatesListener($this->createMock(HttpClientInterface::class), '', '', true);

        $event = new ConsoleTerminateEvent($this->createMock(Command::class), $this->input, $this->output, 0);
        $listener->onConsoleTerminated($event);

        static::assertEmpty($this->output->fetch());
    }

    /**
     * @covers ::onConsoleTerminated
     */
    public function testLocalVersionIsNotAValidVersion(): void
    {
        $listener = new CheckForUpdatesListener($this->createMock(HttpClientInterface::class), 'v1.foo.bar', '', false);

        $event = new ConsoleTerminateEvent($this->createMock(Command::class), $this->input, $this->output, 0);
        $listener->onConsoleTerminated($event);

        static::assertEmpty($this->output->fetch());
    }

    /**
     * @covers ::onConsoleTerminated
     */
    public function testCommandIsDisplayingMachineReadableOutput(): void
    {
        $listener = new CheckForUpdatesListener($this->createMock(HttpClientInterface::class), 'v1.2.3', '', false);

        $command = $this->createMock(BuyCommand::class);
        $command->expects(static::atLeastOnce())->method('isDisplayingMachineReadableOutput')->willReturn(true);

        $event = new ConsoleTerminateEvent($command, $this->input, $this->output, 0);
        $listener->onConsoleTerminated($event);

        static::assertEmpty($this->output->fetch());
    }

    /**
     * @covers ::onConsoleTerminated
     */
    public function testApplicationIsUpToDate(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $listener = new CheckForUpdatesListener($httpClient, 'v1.0.0', '/api/path', false);

        $command = $this->createMock(Command::class);

        $apiResponseContent = ['tag_name' => 'v1.0.0'];
        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('toArray')->willReturn($apiResponseContent);
        $httpClient->expects(static::once())->method('request')->with('GET', '/api/path')->willReturn($apiResponse);

        $event = new ConsoleTerminateEvent($command, $this->input, $this->output, 0);
        $listener->onConsoleTerminated($event);

        static::assertEmpty($this->output->fetch());
    }

    /**
     * @covers ::onConsoleTerminated
     * @covers ::printUpdateNotice
     */
    public function testApplicationIsOutdated(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $listener = new CheckForUpdatesListener($httpClient, 'v1.1.0', '/api', false);

        $command = $this->createMock(Command::class);

        $apiResponseContent = ['tag_name' => 'v1.2.0', 'html_url' => 'release_url'];
        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('toArray')->willReturn($apiResponseContent);
        $httpClient->expects(static::once())->method('request')->with('GET', '/api')->willReturn($apiResponse);

        $event = new ConsoleTerminateEvent($command, $this->input, $this->output, 0);
        $listener->onConsoleTerminated($event);

        static::assertSame(
            '[UPDATE] Bitcoin DCA v1.2.0 is available, your version is v1.1.0: release_url',
            trim($this->output->fetch())
        );
    }

    /**
     * @covers ::onConsoleTerminated
     */
    public function testExceptionHandlingAfterHttpRequest(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $listener = new CheckForUpdatesListener($httpClient, 'v1.1.0', '/api/path/at/github', false);

        $command = $this->createMock(Command::class);
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('toArray')->willThrowException(new Exception('broken!'));
        $httpClient->expects(static::once())->method('request')->with('GET', '/api/path/at/github')->willReturn(
            $apiResponse
        );

        $event = new ConsoleTerminateEvent($command, $input, $output, 0);
        $listener->onConsoleTerminated($event);

        static::assertEmpty($this->output->fetch());
    }
}

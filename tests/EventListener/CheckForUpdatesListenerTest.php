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
use Symfony\Component\Notifier\Event\MessageEvent;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\CheckForUpdatesListener
 * @covers ::__construct
 *
 * @internal
 */
final class CheckForUpdatesListenerTest extends TestCase
{
    private const VERSION = 'v1.1.0';
    private const API_PATH_AT_GITHUB = '/api/path/at/github';

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
     * @covers ::onMessageEvent
     */
    public function testVersionCheckIsDisabledThroughConfiguration(): void
    {
        $listener = new CheckForUpdatesListener($this->createMock(HttpClientInterface::class), '', '', true);
        $event = new ConsoleTerminateEvent($this->createMock(Command::class), $this->input, $this->output, 0);
        $listener->onConsoleTerminated($event);

        static::assertEmpty($this->output->fetch());

        $messageEvent = new MessageEvent(new ChatMessage(''));
        $messageEventSignature = serialize($messageEvent);

        $listener->onMessageEvent($messageEvent);

        static::assertSame(serialize($messageEvent), $messageEventSignature);
    }

    /**
     * @covers ::onConsoleTerminated
     * @covers ::onMessageEvent
     */
    public function testLocalVersionIsNotAValidVersion(): void
    {
        $listener = new CheckForUpdatesListener($this->createMock(HttpClientInterface::class), 'v1.foo.bar', '', false);

        $event = new ConsoleTerminateEvent($this->createMock(Command::class), $this->input, $this->output, 0);
        $listener->onConsoleTerminated($event);

        static::assertEmpty($this->output->fetch());

        $messageEvent = new MessageEvent(new ChatMessage(''));
        $messageEventSignature = serialize($messageEvent);

        $listener->onMessageEvent($messageEvent);

        static::assertSame(serialize($messageEvent), $messageEventSignature);
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
     * @covers ::addUpdateNoticeToMessage
     * @covers ::onConsoleTerminated
     * @covers ::onMessageEvent
     */
    public function testVersionInformationIsAppendedToChatMessageDespiteShowingMachineReadableOutput(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $listener = new CheckForUpdatesListener($httpClient, self::VERSION, '/api', false);

        $command = $this->createMock(BuyCommand::class);
        $command->expects(static::atLeastOnce())->method('isDisplayingMachineReadableOutput')->willReturn(true);

        $apiResponseContent = ['tag_name' => 'v1.2.0', 'html_url' => 'release_url'];
        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('toArray')->willReturn($apiResponseContent);
        $httpClient->expects(static::once())->method('request')->with('GET', '/api')->willReturn($apiResponse);

        $event = new ConsoleTerminateEvent($command, $this->input, $this->output, 0);
        $listener->onConsoleTerminated($event);

        static::assertEmpty($this->output->fetch());

        $messageEvent = new MessageEvent(new ChatMessage(''));
        $messageEventSignature = serialize($messageEvent);

        $listener->onMessageEvent($messageEvent);

        static::assertNotSame(serialize($messageEvent), $messageEventSignature);
        static::assertStringContainsString(
            '<i>Bitcoin DCA v1.2.0 is available, your version is v1.1.0: release_url</i>',
            $messageEvent->getMessage()->getSubject()
        );
    }

    /**
     * @covers ::onConsoleTerminated
     * @covers ::onMessageEvent
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

        $messageEvent = new MessageEvent(new ChatMessage(''));
        $messageEventSignature = serialize($messageEvent);

        $listener->onMessageEvent($messageEvent);

        static::assertSame(serialize($messageEvent), $messageEventSignature);
    }

    /**
     * @covers ::addUpdateNoticeToMessage
     * @covers ::fetchRemoteVersionInformation
     * @covers ::onConsoleTerminated
     * @covers ::onMessageEvent
     * @covers ::printUpdateNoticeToTerminal
     */
    public function testApplicationIsOutdated(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $listener = new CheckForUpdatesListener($httpClient, self::VERSION, '/api', false);

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

        $messageEvent = new MessageEvent(new ChatMessage(''));
        $messageEventSignature = serialize($messageEvent);

        $listener->onMessageEvent($messageEvent);

        static::assertNotSame(serialize($messageEvent), $messageEventSignature);
        static::assertStringContainsString(
            '<i>Bitcoin DCA v1.2.0 is available, your version is v1.1.0: release_url</i>',
            $messageEvent->getMessage()->getSubject()
        );
    }

    /**
     * @covers ::onConsoleTerminated
     * @covers ::onMessageEvent
     */
    public function testExceptionHandlingAfterHttpRequest(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $listener = new CheckForUpdatesListener($httpClient, self::VERSION, self::API_PATH_AT_GITHUB, false);

        $command = $this->createMock(Command::class);
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $apiResponse = $this->createMock(ResponseInterface::class);
        $apiResponse->method('toArray')->willThrowException(new Exception('broken!'));
        $httpClient->expects(static::exactly(2))->method('request')->with('GET', self::API_PATH_AT_GITHUB)->willReturn(
            $apiResponse
        );

        $event = new ConsoleTerminateEvent($command, $input, $output, 0);
        $listener->onConsoleTerminated($event);

        static::assertEmpty($this->output->fetch());

        $messageEvent = new MessageEvent(new ChatMessage(''));
        $messageEventSignature = serialize($messageEvent);

        $listener->onMessageEvent($messageEvent);
        static::assertSame(serialize($messageEvent), $messageEventSignature);
    }

    /**
     * @covers ::onMessageEvent
     */
    public function testListenerDoesNothingOnDifferentMessageType(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $listener = new CheckForUpdatesListener($httpClient, self::VERSION, self::API_PATH_AT_GITHUB, false);
        $messageEvent = new MessageEvent(new SmsMessage('1', ''));

        $messageEventSignature = serialize($messageEvent);
        $listener->onMessageEvent($messageEvent);

        static::assertSame(serialize($messageEvent), $messageEventSignature);
    }
}

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

namespace Jorijn\Bitcoin\Dca\EventListener;

use Jorijn\Bitcoin\Dca\Command\MachineReadableOutputCommandInterface;
use Jorijn\Bitcoin\Dca\Model\RemoteReleaseInformation;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Notifier\Event\MessageEvent;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CheckForUpdatesListener
{
    public const TEST_VALID_VERSION = '/^v\d+\.\d+\.\d+$/';

    protected HttpClientInterface $githubClient;
    protected RemoteReleaseInformation $remoteReleaseInformation;
    protected string $currentVersion;
    protected string $apiPath;
    protected bool $versionCheckDisabled;

    public function __construct(
        HttpClientInterface $githubClient,
        string $currentVersion,
        string $apiPath,
        bool $versionCheckDisabled
    ) {
        $this->githubClient = $githubClient;
        $this->currentVersion = $currentVersion;
        $this->apiPath = $apiPath;
        $this->versionCheckDisabled = $versionCheckDisabled;
    }

    public function onConsoleTerminated(ConsoleTerminateEvent $consoleTerminateEvent): void
    {
        if ($this->versionCheckDisabled || !preg_match(self::TEST_VALID_VERSION, $this->currentVersion)) {
            return;
        }

        $command = $consoleTerminateEvent->getCommand();
        if (
            $command instanceof MachineReadableOutputCommandInterface && $command->isDisplayingMachineReadableOutput()
        ) {
            return;
        }

        try {
            $remoteReleaseInforation = $this->fetchRemoteVersionInformation();

            if ($remoteReleaseInforation->isOutdated()) {
                $this->printUpdateNoticeToTerminal(
                    $consoleTerminateEvent,
                    $remoteReleaseInforation->getReleaseInformation()
                );
            }
        } catch (\Throwable $exception) {
            // fail silently.
        }
    }

    public function onMessageEvent(MessageEvent $messageEvent): void
    {
        if ($this->versionCheckDisabled || !preg_match(self::TEST_VALID_VERSION, $this->currentVersion)) {
            return;
        }

        if (!$messageEvent->getMessage() instanceof ChatMessage) {
            return;
        }

        try {
            $remoteVersionInformation = $this->fetchRemoteVersionInformation();

            if ($remoteVersionInformation->isOutdated()) {
                $this->addUpdateNoticeToMessage(
                    $messageEvent,
                    $remoteVersionInformation->getReleaseInformation()
                );
            }
        } catch (\Throwable $exception) {
            // fail silently
        }
    }

    private function printUpdateNoticeToTerminal(
        ConsoleTerminateEvent $consoleTerminateEvent,
        array $latestReleaseInformation
    ): void {
        $io = new SymfonyStyle($consoleTerminateEvent->getInput(), $consoleTerminateEvent->getOutput());

        $io->block(
            sprintf(
                'Bitcoin DCA %s is available, your version is %s: %s',
                $latestReleaseInformation['tag_name'],
                $this->currentVersion,
                $latestReleaseInformation['html_url']
            ),
            'UPDATE',
            'fg=black;bg=yellow',
            ' ',
            true
        );
    }

    private function fetchRemoteVersionInformation(): RemoteReleaseInformation
    {
        if (!isset($this->remoteReleaseInformation)) {
            $latestReleaseInformation = $this->githubClient->request('GET', $this->apiPath)->toArray();
            $localVersion = preg_replace('/^v/', '', $this->currentVersion);
            $remoteVersion = preg_replace('/^v/', '', $latestReleaseInformation['tag_name']);

            $this->remoteReleaseInformation = new RemoteReleaseInformation(
                $latestReleaseInformation,
                $localVersion,
                $remoteVersion
            );
        }

        return $this->remoteReleaseInformation;
    }

    private function addUpdateNoticeToMessage(MessageEvent $messageEvent, array $latestReleaseInformation): void
    {
        /** @var ChatMessage $message */
        $message = $messageEvent->getMessage();
        $notice = sprintf(
            'Bitcoin DCA %s is available, your version is %s: %s',
            $latestReleaseInformation['tag_name'],
            $this->currentVersion,
            $latestReleaseInformation['html_url']
        );

        $message->subject($message->getSubject().PHP_EOL.PHP_EOL.'<i>'.htmlspecialchars($notice).'</i>');
    }
}

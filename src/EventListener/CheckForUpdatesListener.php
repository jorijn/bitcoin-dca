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
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CheckForUpdatesListener
{
    public const TEST_VALID_VERSION = '/^v\d+\.\d+\.\d+$/';

    protected HttpClientInterface $githubClient;
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
            $latestReleaseInformation = $this->githubClient->request('GET', $this->apiPath)->toArray();
            $localVersion = preg_replace('/^v/', '', $this->currentVersion);
            $remoteVersion = preg_replace('/^v/', '', $latestReleaseInformation['tag_name']);

            if (version_compare($localVersion, $remoteVersion, '<')) {
                $this->printUpdateNotice($consoleTerminateEvent, $latestReleaseInformation);
            }
        } catch (\Throwable $exception) {
            // fail silently.
        }
    }

    private function printUpdateNotice(
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
}

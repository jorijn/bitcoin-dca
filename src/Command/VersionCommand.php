<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VersionCommand extends Command
{
    protected string $versionFile;

    public function __construct(string $versionFile)
    {
        parent::__construct(null);
        $this->versionFile = $versionFile;
    }

    public function configure(): void
    {
        $this
            ->setDescription('Show the current version / build information of Bitcoin DCA')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!file_exists($this->versionFile) || !is_readable($this->versionFile)) {
            $versionInfo = ['version' => 'no version file present, probably a development build'];
        } else {
            $versionInfo = json_decode(file_get_contents($this->versionFile), true, 512, JSON_THROW_ON_ERROR);
        }

        $io->horizontalTable([array_keys($versionInfo)], [array_values($versionInfo)]);

        return Command::SUCCESS;
    }
}

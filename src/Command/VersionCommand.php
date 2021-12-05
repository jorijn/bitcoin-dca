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

namespace Jorijn\Bitcoin\Dca\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VersionCommand extends Command
{
    public function __construct(protected string $versionFile)
    {
        parent::__construct(null);
    }

    public function configure(): void
    {
        $this
            ->setDescription('Show the current version / build information of Bitcoin DCA')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        if (!file_exists($this->versionFile) || !is_readable($this->versionFile)) {
            $versionInfo = ['version' => 'no version file present, probably a development build'];
        } else {
            $versionInfo = json_decode(file_get_contents($this->versionFile), true, 512, JSON_THROW_ON_ERROR);
        }

        $symfonyStyle->horizontalTable([array_keys($versionInfo)], [array_values($versionInfo)]);

        return Command::SUCCESS;
    }
}

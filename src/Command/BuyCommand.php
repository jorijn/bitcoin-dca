<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuyCommand extends Command
{
    public function configure()
    {
        $this
            ->addArgument('amount', InputArgument::REQUIRED, 'The amount of EUR to use for the BUY order')
            ->setDescription('Places a buy order on the exchange');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // ...do this
    }
}

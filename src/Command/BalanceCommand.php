<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Command;

use Jorijn\Bl3pDca\Client\Bl3pClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class BalanceCommand extends Command
{
    protected Bl3pClientInterface $client;

    public function __construct(Bl3pClientInterface $client)
    {
        parent::__construct(null);

        $this->client = $client;
    }

    public function configure(): void
    {
        $this
            ->setDescription('Gets the balance from the exchange and tests the API key');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $response = $this->client->apiCall('GENMKT/money/info');
            $rows = [];

            // FIXME: be more defensive, what happens when these keys don't exist?
            foreach ($response['data']['wallets'] ?? [] as $currency => $wallet) {
                $rows[] = [$currency, $wallet['balance']['display'], $wallet['available']['display']];
            }

            $table = new Table($output);
            $table->setHeaders(['Currency', 'Balance', 'Available']);
            $table->setRows($rows);
            $table->render();

            $io->success('Success!');
        } catch (Throwable $exception) {
            $io->error('API failure: '.$exception->getMessage());
        }

        return 0;
    }
}

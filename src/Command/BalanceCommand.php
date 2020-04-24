<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Command;

use Jorijn\Bl3pDca\Factory\Bl3PClientFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BalanceCommand extends Command
{
    public function configure(): void
    {
        $this
            ->setDescription('Gets the balance from the exchange and tests the API key');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // TODO: think about adding Dependency Injection here >
        $api = (new Bl3PClientFactory())->createApi();
        $io = new SymfonyStyle($input, $output);

        try {
            $response = $api->apiCall('GENMKT/money/info');
            $rows = [];

            // FIXME: be more defensive, what happens when these keys don't exist?
            foreach ($response['data']['wallets'] ?? [] as $currency => $wallet) {
                $rows[] = [$currency, $wallet['balance']['display_short'], $wallet['available']['display_short']];
            }

            $table = new Table($output);
            $table->setHeaders(['Currency', 'Balance', 'Available']);
            $table->setRows($rows);
            $table->render();

            $io->success('Success!');
        } catch (\Throwable $exception) {
            $io->error('API failure: '.$exception->getMessage());
        }

        return 0;
    }
}

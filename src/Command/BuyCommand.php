<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Command;

use Jorijn\Bl3pDca\Factory\Bl3PClientFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuyCommand extends Command
{
    public function configure()
    {
        $this
            ->addArgument('amount', InputArgument::REQUIRED, 'The amount of EUR to use for the BUY order')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'If supplied, will not confirm the amount and place the order immediately')
            ->setDescription('Places a buy order on the exchange');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // TODO: think about adding Dependency Injection here >
        $api = (new Bl3PClientFactory())->createApi();
        $io = new SymfonyStyle($input, $output);
        $amount = $input->getArgument('amount');

        if (!preg_match('/^\d+$/', $amount)) {
            $io->error('Amount should be numeric, e.g. 10');

            return 1;
        }

        if (!$input->getOption('yes')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure you want to place an order for EUR '.$amount.'? [y/N] ', false);
            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $params = [
            'type' => 'bid',
            'amount_funds_int' => (int) $amount * 100000,
            'fee_currency' => 'BTC',
        ];

        // FIXME: be more defensive about this part, stuff could break here and no one likes error messages when it comes to money
        $result = $api->apiCall('BTCEUR/money/order/add', $params);

        // fetch the order info
        $orderInfo = $api->apiCall('BTCEUR/money/order/result', ['order_id' => $result['data']['order_id']]);

        $io->success(sprintf(
            'Bought: %s, EUR: %s, price: %s, spent fees: %s',
            $orderInfo['data']['total_amount']['display'],
            $orderInfo['data']['total_spent']['display_short'],
            $orderInfo['data']['avg_cost']['display_short'],
            $orderInfo['data']['total_fee']['display']
        ));

        return 0;
    }
}

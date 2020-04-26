<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Command;

use Jorijn\Bl3pDca\Client\Bl3pClientInterface;
use Jorijn\Bl3pDca\Repository\TaggedBalanceRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuyCommand extends Command
{
    public const ORDER_TIMEOUT = 30;

    protected Bl3pClientInterface $client;
    /** @var TaggedBalanceRepositoryInterface */
    protected TaggedBalanceRepositoryInterface $balanceRepository;

    public function __construct(
        string $name,
        Bl3pClientInterface $client,
        TaggedBalanceRepositoryInterface $balanceRepository
    ) {
        parent::__construct($name);

        $this->client = $client;
        $this->balanceRepository = $balanceRepository;
    }

    public function configure(): void
    {
        $this
            ->addArgument('amount', InputArgument::REQUIRED, 'The amount of EUR to use for the BUY order')
            ->addOption('yes', 'y', InputOption::VALUE_NONE,
                'If supplied, will not confirm the amount and place the order immediately')
            ->addOption('tag', 't', InputOption::VALUE_REQUIRED,
                'If supplied, this will increase the balance in the database for this tag with the purchased amount of Bitcoin')
            ->setDescription('Places a buy order on the exchange');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $amount = $input->getArgument('amount');

        if (!preg_match('/^\d+$/', $amount)) {
            $io->error('Amount should be numeric, e.g. 10');

            return 1;
        }

        if (!$input->getOption('yes')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure you want to place an order for EUR '.$amount.'? [y/N] ',
                false);
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
        $result = $this->client->apiCall('BTCEUR/money/order/add', $params);

        // fetch the order info and wait until the order has been filled
        $failureAt = time() + self::ORDER_TIMEOUT;
        do {
            $orderInfo = $this->client->apiCall('BTCEUR/money/order/result', [
                'order_id' => $result['data']['order_id'],
            ]);

            if ('closed' === $orderInfo['data']['status']) {
                break;
            }

            sleep(1);
        } while (time() < $failureAt);

        if ('closed' === $orderInfo['data']['status']) {
            $io->success(sprintf(
                'Bought: %s, EUR: %s, price: %s, spent fees: %s',
                $orderInfo['data']['total_amount']['display'],
                $orderInfo['data']['total_spent']['display_short'],
                $orderInfo['data']['avg_cost']['display_short'],
                $orderInfo['data']['total_fee']['display']
            ));

            if ($tagValue = $input->getOption('tag')) {
                $this->balanceRepository->increaseTagBalance($tagValue, (int) $orderInfo['data']['total_amount']['value_int']);
            }

            return 0;
        }

        $this->client->apiCall('BTCEUR/money/order/cancel', ['order_id' => $result['data']['order_id']]);

        $io->error('Was not able to fill a MARKET order within the specified timeout ('.self::ORDER_TIMEOUT.' seconds). The order was cancelled.');

        return 1;
    }
}

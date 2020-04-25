<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Command;

use Jorijn\Bl3pDca\Client\Bl3PClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class WithdrawCommand extends Command
{
    /** @var int withdraw fee in satoshis */
    public const WITHDRAW_FEE = 30000;

    protected Bl3PClientInterface $client;

    public function __construct(string $name, Bl3PClientInterface $client)
    {
        parent::__construct($name);

        $this->client = $client;
    }

    public function configure(): void
    {
        $this
            ->addOption('all', 'a', InputOption::VALUE_NONE,
                'If supplied, will withdraw all available Bitcoin to the configured address')
            ->addOption('yes', 'y', InputOption::VALUE_NONE,
                'If supplied, will not confirm the withdraw go ahead immediately')
            ->setDescription('Withdraw Bitcoin from Bl3P');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('all')) {
            $io->error('Only allows withdraw for all funds right now, will be updated in the future. Supply --all to proceed.');

            return 1;
        }

        $balanceToWithdraw = $this->getBalanceToWithdraw($input);
        if (0 === $balanceToWithdraw) {
            $io->error('No balance available, better start saving something!');

            return 0;
        }

        // TODO find out if better validation is available here
        $address = $_SERVER['BL3P_WITHDRAW_ADDRESS'];
        if (empty($address)) {
            $io->error('No address available. Did you configure BL3P_WITHDRAW_ADDRESS?');

            return 1;
        }

        if (!$input->getOption('yes')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(sprintf(
                'Ready to withdraw %s BTC to Bitcoin Address %s? A fee of %s will be taken as withdraw fee [y/N]: ',
                $balanceToWithdraw / 100000000,
                $address,
                self::WITHDRAW_FEE / 100000000
            ), false);

            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $response = $this->client->apiCall('GENMKT/money/withdraw', [
            'currency' => 'BTC',
            'address' => $address,
            'amount_int' => ($balanceToWithdraw - self::WITHDRAW_FEE),
        ]);

        $io->success('Withdraw is being processed as ID '.$response['data']['id']);

        return 0;
    }

    protected function getBalanceToWithdraw(InputInterface $input): int
    {
        if ($input->getOption('all')) {
            $response = $this->client->apiCall('GENMKT/money/info');

            return (int) ($response['data']['wallets']['BTC']['available']['value_int'] ?? 0);
        }

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Command;

use Jorijn\Bitcoin\Dca\Service\WithdrawService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WithdrawCommand extends Command
{
    protected WithdrawService $withdrawService;

    public function __construct(WithdrawService $withdrawService)
    {
        parent::__construct(null);

        $this->withdrawService = $withdrawService;
    }

    public function configure(): void
    {
        $this
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'If supplied, will withdraw all available Bitcoin to the configured address'
            )
            ->addOption(
                'yes',
                'y',
                InputOption::VALUE_NONE,
                'If supplied, will not confirm the withdraw go ahead immediately'
            )
            ->addOption(
                'tag',
                't',
                InputOption::VALUE_REQUIRED,
                'If supplied, will limit the withdrawal to the balance available for this tag'
            )
            ->setDescription('Withdraw Bitcoin from Bl3P')
        ;
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('all')) {
            $io->error('Only allows withdraw for all funds right now, will be updated in the future. Supply --all to proceed.');

            return 1;
        }

        $balanceToWithdraw = $this->withdrawService->getBalance($input->getOption('all'), $input->getOption('tag'));
        $addressToWithdrawTo = $this->withdrawService->getRecipientAddress();

        if (0 === $balanceToWithdraw) {
            $io->error('No balance available, better start saving something!');

            return 0;
        }

        if (!$input->getOption('yes')) {
            $question = sprintf(
                'Ready to withdraw %s BTC to Bitcoin Address %s? A fee of %s will be taken as withdraw fee.',
                $balanceToWithdraw / 100000000,
                $addressToWithdrawTo,
                WithdrawService::WITHDRAW_FEE / 100000000
            );

            if (!$io->confirm($question, false)) {
                return 0;
            }
        }

        $completedWithdraw = $this->withdrawService->withdraw(
            $balanceToWithdraw,
            $addressToWithdrawTo,
            $input->getOption('tag')
        );

        $io->success('Withdraw is being processed as ID '.$completedWithdraw->getId());

        return 0;
    }
}

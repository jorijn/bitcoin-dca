<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Command;

use Jorijn\Bl3pDca\Client\Bl3pClientInterface;
use Jorijn\Bl3pDca\Event\WithdrawSuccessEvent;
use Jorijn\Bl3pDca\Provider\WithdrawAddressProviderInterface;
use Jorijn\Bl3pDca\Repository\TaggedIntegerRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
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

    /** @var WithdrawAddressProviderInterface[] */
    protected iterable $addressProviders;
    protected Bl3pClientInterface $client;
    protected TaggedIntegerRepositoryInterface $balanceRepository;
    protected EventDispatcherInterface $dispatcher;

    public function __construct(
        Bl3pClientInterface $client,
        iterable $addressProviders,
        TaggedIntegerRepositoryInterface $balanceRepository,
        EventDispatcherInterface $dispatcher
    ) {
        parent::__construct(null);

        $this->client = $client;
        $this->addressProviders = $addressProviders;
        $this->balanceRepository = $balanceRepository;
        $this->dispatcher = $dispatcher;
    }

    public function configure(): void
    {
        $this
            ->addOption('all', 'a', InputOption::VALUE_NONE,
                'If supplied, will withdraw all available Bitcoin to the configured address')
            ->addOption('yes', 'y', InputOption::VALUE_NONE,
                'If supplied, will not confirm the withdraw go ahead immediately')
            ->addOption('tag', 't', InputOption::VALUE_REQUIRED,
                'If supplied, will limit the withdrawal to the balance available for this tag')
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
        $addressToWithdrawTo = $this->getAddressToWithdrawTo();

        if (0 === $balanceToWithdraw) {
            $io->error('No balance available, better start saving something!');

            return 0;
        }

        if (!$input->getOption('yes')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(sprintf(
                'Ready to withdraw %s BTC to Bitcoin Address %s? A fee of %s will be taken as withdraw fee [y/N]: ',
                $balanceToWithdraw / 100000000,
                $addressToWithdrawTo,
                self::WITHDRAW_FEE / 100000000
            ), false);

            if (!$helper->ask($input, $output, $question)) {
                return 0;
            }
        }

        $netAmountToWithdraw = $balanceToWithdraw - self::WITHDRAW_FEE;
        $response = $this->client->apiCall('GENMKT/money/withdraw', [
            'currency' => 'BTC',
            'address' => $addressToWithdrawTo,
            'amount_int' => $netAmountToWithdraw,
        ]);

        $eventContext = [];
        if ($tagValue = $input->getOption('tag')) {
            $this->balanceRepository->set($tagValue, 0);
            $eventContext['tag'] = $tagValue;
        }

        $this->dispatcher->dispatch(
            new WithdrawSuccessEvent($addressToWithdrawTo, $netAmountToWithdraw, $eventContext)
        );

        $io->success('Withdraw is being processed as ID '.$response['data']['id']);

        return 0;
    }

    protected function getBalanceToWithdraw(InputInterface $input): int
    {
        if ($input->getOption('all')) {
            $response = $this->client->apiCall('GENMKT/money/info');
            $maxAvailableBalance = (int) ($response['data']['wallets']['BTC']['available']['value_int'] ?? 0);

            if ($tagValue = $input->getOption('tag')) {
                $tagBalance = $this->balanceRepository->get($tagValue);

                // limit the balance to what comes first: the tagged balance, or the maximum balance
                return $tagBalance <= $maxAvailableBalance ? $tagBalance : $maxAvailableBalance;
            }

            return $maxAvailableBalance;
        }

        return 0;
    }

    protected function getAddressToWithdrawTo(): string
    {
        foreach ($this->addressProviders as $addressProvider) {
            try {
                return $addressProvider->provide();
            } catch (\Throwable $exception) {
                // allowed to fail
            }
        }

        throw new \RuntimeException('Unable to determine address to withdraw to, did you configure any?');
    }
}

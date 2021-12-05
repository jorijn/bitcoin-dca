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

use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Jorijn\Bitcoin\Dca\Service\BuyService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class BuyCommand extends Command implements MachineReadableOutputCommandInterface
{
    private bool $isDisplayingMachineReadableOutput = false;

    public function __construct(
        protected BuyService $buyService,
        protected SerializerInterface $serializer,
        protected string $baseCurrency
    ) {
        parent::__construct(null);
    }

    public function configure(): void
    {
        $this
            ->addArgument('amount', InputArgument::REQUIRED, 'The amount of base currency to use for the BUY order')
            ->addOption(
                'yes',
                'y',
                InputOption::VALUE_NONE,
                'If supplied, will not confirm the amount and place the order immediately'
            )
            ->addOption(
                'tag',
                't',
                InputOption::VALUE_REQUIRED,
                'If supplied, this will increase the balance in the database for this tag with the purchased amount of Bitcoin'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'If supplied, determines how the purchase order is displayed. Available options: human, csv, yaml, xml, json. Default: human'
            )
            ->setDescription('Places a buy order on the exchange')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $amount = (string) $input->getArgument('amount');
        if (!preg_match('/^\d+$/', $amount)) {
            $symfonyStyle->error('Amount should be numeric, e.g. 10');

            return 1;
        }

        if (!$input->getOption('yes') && !$symfonyStyle->confirm(
            'Are you sure you want to place an order for '.$this->baseCurrency.' '.$amount.'?',
            false
        )) {
            return 0;
        }

        try {
            $completedBuyOrder = $this->buyService->buy((int) $amount, $input->getOption('tag'));

            $this->displayFormattedPurchaseOrder($completedBuyOrder, $symfonyStyle, $input->getOption('output'));

            return 0;
        } catch (Throwable $exception) {
            $symfonyStyle->error($exception->getMessage());
        }

        return 1;
    }

    public function isDisplayingMachineReadableOutput(): bool
    {
        return $this->isDisplayingMachineReadableOutput;
    }

    private function displayFormattedPurchaseOrder(
        CompletedBuyOrder $completedBuyOrder,
        SymfonyStyle $symfonyStyle,
        ?string $requestedFormat
    ): void {
        switch ($requestedFormat) {
            case 'csv':
            case 'json':
            case 'xml':
            case 'yaml':
                $this->isDisplayingMachineReadableOutput = true;
                $symfonyStyle->write($this->serializer->serialize($completedBuyOrder, $requestedFormat));

                break;

            default:
                $symfonyStyle->success(
                    sprintf(
                        'Bought: %s, %s: %s, price: %s, spent fees: %s',
                        $completedBuyOrder->getDisplayAmountBought(),
                        $this->baseCurrency,
                        $completedBuyOrder->getDisplayAmountSpent(),
                        $completedBuyOrder->getDisplayAveragePrice(),
                        $completedBuyOrder->getDisplayFeesSpent()
                    )
                );

                break;
        }
    }
}

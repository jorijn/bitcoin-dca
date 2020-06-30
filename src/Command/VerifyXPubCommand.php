<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\Command;

use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponent;
use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponentInterface;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VerifyXPubCommand extends Command
{
    protected ?string $configuredKey;
    protected string $environmentKey;
    protected TaggedIntegerRepositoryInterface $xpubRepository;
    protected AddressFromMasterPublicKeyComponentInterface $keyFactory;

    public function __construct(
        AddressFromMasterPublicKeyComponentInterface $keyFactory,
        TaggedIntegerRepositoryInterface $xpubRepository,
        ?string $configuredKey,
        string $environmentKey
    ) {
        parent::__construct(null);

        $this->keyFactory = $keyFactory;
        $this->configuredKey = $configuredKey;
        $this->environmentKey = $environmentKey;
        $this->xpubRepository = $xpubRepository;
    }

    public function configure(): void
    {
        $this
            ->setDescription('If configured, this command displays derived address from your master public key')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (empty($this->configuredKey)) {
            $io->error('Unable to find any configured X/Z/Y-pub. Did you configure '.$this->environmentKey.'?');

            return 1;
        }

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['#', 'Address', 'Next Withdraw']);

        $nextIndex = $this->xpubRepository->get($this->configuredKey);
        $baseIndex = ($nextIndex - 5);
        $baseIndex = $baseIndex < 0 ? 0 : $baseIndex;

        for ($x = $baseIndex; $x < ($baseIndex + 10); ++$x) {
            $derivationPath = '0/'.$x;
            $table->addRow(
                [$x, $this->keyFactory->derive($this->configuredKey, $derivationPath), $x === $nextIndex ? '<' : null]
            );
        }

        $table->render();
        $io->warning('Make sure these addresses match those in your client, do not use the withdraw function if they do not.');

        return 0;
    }
}

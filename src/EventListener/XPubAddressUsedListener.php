<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\EventListener;

use Jorijn\Bl3pDca\Event\WithdrawSuccessEvent;
use Jorijn\Bl3pDca\Factory\AddressFromMasterPublicKeyFactory;
use Jorijn\Bl3pDca\Repository\TaggedIntegerRepositoryInterface;
use Psr\Log\LoggerInterface;

class XPubAddressUsedListener
{
    protected TaggedIntegerRepositoryInterface $xpubRepository;
    protected AddressFromMasterPublicKeyFactory $keyFactory;
    protected LoggerInterface $logger;
    protected ?string $configuredXPub;

    public function __construct(
        TaggedIntegerRepositoryInterface $xpubRepository,
        AddressFromMasterPublicKeyFactory $keyFactory,
        LoggerInterface $logger,
        ?string $configuredXPub
    ) {
        $this->xpubRepository = $xpubRepository;
        $this->configuredXPub = $configuredXPub;
        $this->keyFactory = $keyFactory;
        $this->logger = $logger;
    }

    public function onWithdrawAddressUsed(WithdrawSuccessEvent $event): void
    {
        if (null === $this->configuredXPub) {
            return;
        }

        try {
            $activeIndex = $this->xpubRepository->get($this->configuredXPub);
            $activeDerivationPath = sprintf('0/%d', $activeIndex);
            $derivedAddress = $this->keyFactory->derive($this->configuredXPub, $activeDerivationPath);

            // validate that given address matches the one derived from the xpub
            if ($derivedAddress !== $event->getAddress()) {
                return;
            }

            $this->logger->info('found successful withdraw for configured xpub, increasing index', [
                'xpub' => $this->configuredXPub,
                'used_index' => $activeIndex,
                'used_address' => $derivedAddress,
                'derivation_path' => $activeDerivationPath,
            ]);

            // we have a match, increase the index in the database so a new address is returned next time
            $this->xpubRepository->increase($this->configuredXPub);
        } catch (\Throwable $exception) {
            $this->logger->error('failed to determine / increase xpub index', [
                'xpub' => $this->configuredXPub,
                'reason' => $exception->getMessage() ?: get_class($exception),
            ]);

            throw $exception;
        }
    }
}

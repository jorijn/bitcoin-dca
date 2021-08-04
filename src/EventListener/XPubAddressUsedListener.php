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

namespace Jorijn\Bitcoin\Dca\EventListener;

use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponent;
use Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Psr\Log\LoggerInterface;

class XPubAddressUsedListener
{
    protected TaggedIntegerRepositoryInterface $xpubRepository;
    protected AddressFromMasterPublicKeyComponent $keyFactory;
    protected LoggerInterface $logger;
    protected ?string $configuredXPub;

    public function __construct(
        TaggedIntegerRepositoryInterface $xpubRepository,
        AddressFromMasterPublicKeyComponent $keyFactory,
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
            $completedWithdraw = $event->getCompletedWithdraw();

            // validate that given address matches the one derived from the xpub
            if ($derivedAddress !== $completedWithdraw->getRecipientAddress()) {
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
                'reason' => $exception->getMessage() ?: \get_class($exception),
            ]);

            throw $exception;
        }
    }
}

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

use Jorijn\Bitcoin\Dca\Component\AddressFromMasterPublicKeyComponentInterface;
use Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class XPubAddressUsedListener
{
    public function __construct(
        protected TaggedIntegerRepositoryInterface $taggedIntegerRepository,
        protected AddressFromMasterPublicKeyComponentInterface $addressFromMasterPublicKeyComponent,
        protected LoggerInterface $logger,
        protected ?string $configuredXPub
    ) {
    }

    public function onWithdrawAddressUsed(WithdrawSuccessEvent $withdrawSuccessEvent): void
    {
        if (null === $this->configuredXPub) {
            return;
        }

        try {
            $activeIndex = $this->taggedIntegerRepository->get($this->configuredXPub);
            $activeDerivationPath = sprintf('0/%d', $activeIndex);
            $derivedAddress = $this->addressFromMasterPublicKeyComponent->derive(
                $this->configuredXPub,
                $activeDerivationPath
            );
            $completedWithdraw = $withdrawSuccessEvent->getCompletedWithdraw();

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
            $this->taggedIntegerRepository->increase($this->configuredXPub);
        } catch (Throwable $exception) {
            $this->logger->error('failed to determine / increase xpub index', [
                'xpub' => $this->configuredXPub,
                'reason' => $exception->getMessage() ?: $exception::class,
            ]);

            throw $exception;
        }
    }
}

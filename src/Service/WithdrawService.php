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

namespace Jorijn\Bitcoin\Dca\Service;

use Jorijn\Bitcoin\Dca\Client\Bl3pClientInterface;
use Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent;
use Jorijn\Bitcoin\Dca\Exception\NoExchangeAvailableException;
use Jorijn\Bitcoin\Dca\Exception\NoRecipientAddressAvailableException;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class WithdrawService
{
    protected Bl3pClientInterface $client;

    public function __construct(
        protected iterable $addressProviders,
        protected iterable $configuredServices,
        protected TaggedIntegerRepositoryInterface $taggedIntegerRepository,
        protected EventDispatcherInterface $eventDispatcher,
        protected LoggerInterface $logger,
        protected string $configuredExchange
    ) {
    }

    public function getWithdrawFeeInSatoshis(): int
    {
        return $this->getActiveService()->getWithdrawFeeInSatoshis();
    }

    public function withdraw(int $balanceToWithdraw, string $addressToWithdrawTo, string $tag = null): CompletedWithdraw
    {
        try {
            $completedWithdraw = $this->getActiveService()->withdraw($balanceToWithdraw, $addressToWithdrawTo);

            $this->eventDispatcher->dispatch(
                new WithdrawSuccessEvent(
                    $completedWithdraw,
                    $tag
                )
            );

            $this->logger->info('withdraw to {address} successful, processing as ID {data.id}', [
                'tag' => $tag,
                'balance' => $balanceToWithdraw,
                'address' => $addressToWithdrawTo,
                'data' => ['id' => $completedWithdraw->getId()],
            ]);

            return $completedWithdraw;
        } catch (\Throwable $exception) {
            $this->logger->error('withdraw to {address} failed', [
                'tag' => $tag,
                'balance' => $balanceToWithdraw,
                'address' => $addressToWithdrawTo,
                'reason' => $exception->getMessage() ?: $exception::class,
            ]);

            throw $exception;
        }
    }

    public function getBalance(string $tag = null): int
    {
        $maxAvailableBalance = $this->getActiveService()->getAvailableBalance();

        if ($tag) {
            $tagBalance = $this->taggedIntegerRepository->get($tag);

            // limit the balance to what comes first: the tagged balance, or the maximum balance
            return $tagBalance <= $maxAvailableBalance ? $tagBalance : $maxAvailableBalance;
        }

        return $maxAvailableBalance;
    }

    public function getRecipientAddress(): string
    {
        foreach ($this->addressProviders as $addressProvider) {
            try {
                return $addressProvider->provide();
            } catch (Throwable) {
                // allowed to fail
            }
        }

        throw new NoRecipientAddressAvailableException(
            'Unable to determine address to withdraw to, did you configure any?'
        );
    }

    protected function getActiveService(): WithdrawServiceInterface
    {
        foreach ($this->configuredServices as $configuredService) {
            if ($configuredService->supportsExchange($this->configuredExchange)) {
                return $configuredService;
            }
        }

        $errorMessage = 'no exchange was available to perform this withdraw';
        $this->logger->error($errorMessage);

        throw new NoExchangeAvailableException($errorMessage);
    }
}

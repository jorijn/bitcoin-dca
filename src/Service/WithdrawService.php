<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Service;

use Jorijn\Bl3pDca\Client\Bl3pClientInterface;
use Jorijn\Bl3pDca\Event\WithdrawSuccessEvent;
use Jorijn\Bl3pDca\Exception\NoRecipientAddressAvailableException;
use Jorijn\Bl3pDca\Model\CompletedWithdraw;
use Jorijn\Bl3pDca\Provider\WithdrawAddressProviderInterface;
use Jorijn\Bl3pDca\Repository\TaggedIntegerRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class WithdrawService
{
    /** @var int withdraw fee in satoshis */
    public const WITHDRAW_FEE = 30000;

    /** @var WithdrawAddressProviderInterface[] */
    protected iterable $addressProviders;
    protected Bl3pClientInterface $client;
    protected TaggedIntegerRepositoryInterface $balanceRepository;
    protected EventDispatcherInterface $dispatcher;
    protected LoggerInterface $logger;

    public function __construct(
        Bl3pClientInterface $client,
        iterable $addressProviders,
        TaggedIntegerRepositoryInterface $balanceRepository,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->addressProviders = $addressProviders;
        $this->balanceRepository = $balanceRepository;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    public function withdraw(int $balanceToWithdraw, string $addressToWithdrawTo, string $tag = null): CompletedWithdraw
    {
        $netAmountToWithdraw = $balanceToWithdraw - self::WITHDRAW_FEE;
        $response = $this->client->apiCall('GENMKT/money/withdraw', [
            'currency' => 'BTC',
            'address' => $addressToWithdrawTo,
            'amount_int' => $netAmountToWithdraw,
        ]);

        $completedWithdraw = new CompletedWithdraw($addressToWithdrawTo, $netAmountToWithdraw, $response['data']['id']);

        $this->dispatcher->dispatch(
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
    }

    public function getBalance($all = true, string $tag = null): int
    {
        if (true === $all) {
            $response = $this->client->apiCall('GENMKT/money/info');
            $maxAvailableBalance = (int) ($response['data']['wallets']['BTC']['available']['value_int'] ?? 0);

            if (!empty($tag)) {
                $tagBalance = $this->balanceRepository->get($tag);

                // limit the balance to what comes first: the tagged balance, or the maximum balance
                return $tagBalance <= $maxAvailableBalance ? $tagBalance : $maxAvailableBalance;
            }

            return $maxAvailableBalance;
        }

        return 0;
    }

    public function getRecipientAddress(): string
    {
        foreach ($this->addressProviders as $addressProvider) {
            try {
                return $addressProvider->provide();
            } catch (\Throwable $exception) {
                // allowed to fail
            }
        }

        throw new NoRecipientAddressAvailableException('Unable to determine address to withdraw to, did you configure any?');
    }
}

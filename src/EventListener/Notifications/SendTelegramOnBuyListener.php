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

namespace Jorijn\Bitcoin\Dca\EventListener\Notifications;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramTransport;
use Symfony\Component\Notifier\Message\ChatMessage;

class SendTelegramOnBuyListener
{
    protected TelegramTransport $transport;
    protected EventDispatcherInterface $dispatcher;
    protected string $exchange;
    protected bool $isEnabled;

    public function __construct(
        TelegramTransport $transport,
        EventDispatcherInterface $dispatcher,
        string $exchange,
        bool $isEnabled
    ) {
        $this->transport = $transport;
        $this->exchange = $exchange;
        $this->isEnabled = $isEnabled;
        $this->dispatcher = $dispatcher;
    }

    public function onBuy(BuySuccessEvent $event): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $formattedSats = number_format($event->getBuyOrder()->getAmountInSatoshis());

        $markdownMessage = <<<TLGRM
<strong>💰 Bitcoin-DCA just bought {$formattedSats} sat.</strong>

Transaction overview:

Purchased: <strong>{$event->getBuyOrder()->getDisplayAmountBought()}</strong>
Spent: <strong>{$event->getBuyOrder()->getDisplayAmountSpent()} {$event->getBuyOrder()->getDisplayAmountSpentCurrency()}</strong>
Fee: <strong>{$event->getBuyOrder()->getDisplayFeesSpent()}</strong>
Price: <strong>{$event->getBuyOrder()->getDisplayAveragePrice()}</strong>
TLGRM;

        $message = new ChatMessage(
            $markdownMessage,
            new TelegramOptions(
                [
                    'parse_mode' => TelegramOptions::PARSE_MODE_HTML,
                    'disable_web_page_preview' => true,
                ]
            )
        );

        $this->transport->send($message);
    }
}

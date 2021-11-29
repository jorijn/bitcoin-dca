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
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\Message\ChatMessage;

class SendTelegramOnBuyListener extends AbstractSendTelegramListener
{
    public function onBuy(BuySuccessEvent $event): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $formattedSats = number_format($event->getBuyOrder()->getAmountInSatoshis());
        $exchange = ucfirst($this->getExchange());

        $htmlMessage = <<<TLGRM
<strong>💰 Bitcoin-DCA just bought {$formattedSats} sat at {$exchange}.</strong>

Transaction overview:

Purchased: <strong>{$event->getBuyOrder()->getDisplayAmountBought()}</strong>
Spent: <strong>{$event->getBuyOrder()->getDisplayAmountSpent()}</strong>
Fee: <strong>{$event->getBuyOrder()->getDisplayFeesSpent()}</strong>
Price: <strong>{$event->getBuyOrder()->getDisplayAveragePrice()}</strong>
TLGRM;

        if ($event->getTag()) {
            $htmlMessage .= PHP_EOL.'Tag: <strong>'.htmlspecialchars($event->getTag()).'</strong>';
        }

        $message = new ChatMessage(
            $htmlMessage,
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

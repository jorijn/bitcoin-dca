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
    public function onBuy(BuySuccessEvent $buySuccessEvent): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $formattedSats = number_format($buySuccessEvent->getBuyOrder()->getAmountInSatoshis());
        $exchange = ucfirst($this->getExchange());

        $htmlMessage = <<<TLGRM
            <strong>💰 Bitcoin-DCA just bought {$formattedSats} sat at {$exchange}.</strong>

            Transaction overview:

            Purchased: <strong>{$buySuccessEvent->getBuyOrder()->getDisplayAmountBought()}</strong>
            Spent: <strong>{$buySuccessEvent->getBuyOrder()->getDisplayAmountSpent()}</strong>
            Fee: <strong>{$buySuccessEvent->getBuyOrder()->getDisplayFeesSpent()}</strong>
            Price: <strong>{$buySuccessEvent->getBuyOrder()->getDisplayAveragePrice()}</strong>
            TLGRM;

        if ($buySuccessEvent->getTag()) {
            $htmlMessage .= PHP_EOL.'Tag: <strong>'.htmlspecialchars($buySuccessEvent->getTag()).'</strong>';
        }

        $chatMessage = new ChatMessage(
            $htmlMessage,
            new TelegramOptions(
                [
                    'parse_mode' => TelegramOptions::PARSE_MODE_HTML,
                    'disable_web_page_preview' => true,
                ]
            )
        );

        $this->telegramTransport->send($chatMessage);
    }
}

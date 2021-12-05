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

use Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\Message\ChatMessage;

class SendTelegramOnWithdrawListener extends AbstractSendTelegramListener
{
    public function onWithdraw(WithdrawSuccessEvent $withdrawSuccessEvent): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $completedWithdraw = $withdrawSuccessEvent->getCompletedWithdraw();
        $formattedSats = number_format($completedWithdraw->getNetAmount());

        $htmlMessage = <<<TLGRM
            <strong>💰 Bitcoin-DCA just withdrew {$formattedSats} sat.</strong>

            Transaction overview:

            Address: <strong>{$completedWithdraw->getRecipientAddress()}</strong>
            ID: <strong>{$completedWithdraw->getId()}</strong>
            TLGRM;

        if ($withdrawSuccessEvent->getTag()) {
            $htmlMessage .= PHP_EOL.'Tag: <strong>'.htmlspecialchars($withdrawSuccessEvent->getTag()).'</strong>';
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

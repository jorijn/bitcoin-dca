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

class SendEmailOnBuyListener extends AbstractSendEmailListener
{
    final public const NOTIFICATION_SUBJECT_LINE = 'You bought %s sats on %s';

    public function onBuy(BuySuccessEvent $buySuccessEvent): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $templateVariables = array_merge(
            [
                'buyOrder' => $buySuccessEvent->getBuyOrder(),
                'tag' => $buySuccessEvent->getTag(),
            ],
            $this->getTemplateVariables()
        );

        $html = $this->renderTemplate($this->templateLocation, $templateVariables);

        $email = $this->createEmail()
            ->subject(
                sprintf(
                    '[%s] %s',
                    $this->notificationEmailConfiguration->getSubjectPrefix(),
                    sprintf(
                        self::NOTIFICATION_SUBJECT_LINE,
                        number_format($buySuccessEvent->getBuyOrder()->getAmountInSatoshis()),
                        ucfirst($this->notificationEmailTemplateInformation->getExchange())
                    )
                )
            )
            ->html($html)
            ->text($this->htmlConverter->convert($html))
        ;

        $this->mailer->send($email);
    }
}

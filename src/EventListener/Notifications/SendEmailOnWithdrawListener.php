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

class SendEmailOnWithdrawListener extends AbstractSendEmailListener
{
    final public const NOTIFICATION_SUBJECT_LINE = 'You withdrew %s satoshis from %s';

    public function onWithdraw(WithdrawSuccessEvent $withdrawSuccessEvent): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $templateVariables = array_merge(
            [
                'completedWithdraw' => $withdrawSuccessEvent->getCompletedWithdraw(),
                'tag' => $withdrawSuccessEvent->getTag(),
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
                        number_format($withdrawSuccessEvent->getCompletedWithdraw()->getNetAmount()),
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

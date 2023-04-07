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

namespace Tests\Jorijn\Bitcoin\Dca\EventListener\Notifications;

use Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent;
use Jorijn\Bitcoin\Dca\EventListener\Notifications\SendEmailOnWithdrawListener;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use Symfony\Component\Mime\Email;

/**
 * @internal
 *
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\Notifications\SendEmailOnWithdrawListener
 */
final class SendEmailOnWithdrawListenerTest extends TesterOfAbstractSendEmailListener
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new SendEmailOnWithdrawListener(
            $this->notifier,
            $this->htmlConverter,
            $this->emailConfiguration,
            $this->templateConfiguration,
            true
        );

        $this->listener->setTemplateLocation($this->templateLocation);
    }

    /**
     * @covers ::onWithdraw
     */
    public function testListenerDoesNotActWhenDisabled(): void
    {
        $this->listener = new SendEmailOnWithdrawListener(
            $this->notifier,
            $this->htmlConverter,
            $this->emailConfiguration,
            $this->templateConfiguration,
            false
        );

        $this->notifier->expects(static::never())->method('send');

        $withdrawSuccessEvent = new WithdrawSuccessEvent(new CompletedWithdraw('address', 1, '1'));
        $this->listener->onWithdraw($withdrawSuccessEvent);
    }

    /**
     * @covers ::onWithdraw
     */
    public function testAssertThatEmailIsSentOnWithdrawEvent(): void
    {
        $address = 'a'.random_int(10000, 20000);
        $id = (string) random_int(10, 20);
        $amount = random_int(10000, 20000);
        $completedWithdraw = (new CompletedWithdraw($address, $amount, $id));
        $tag = 't'.random_int(1000, 2000);

        $withdrawSuccessEvent = new WithdrawSuccessEvent($completedWithdraw, $tag);

        $this->notifier
            ->expects(static::once())
            ->method('send')
            ->with(
                static::callback(function (Email $email) use ($amount): bool {
                    self::assertSame(
                        sprintf(
                            '[%s] %s',
                            $this->subjectPrefix,
                            sprintf(
                                SendEmailOnWithdrawListener::NOTIFICATION_SUBJECT_LINE,
                                number_format($amount),
                                ucfirst($this->exchange)
                            )
                        ),
                        $email->getSubject()
                    );

                    return true;
                })
            )
        ;

        $this->listener->onWithdraw($withdrawSuccessEvent);
    }
}

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

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\EventListener\Notifications\SendEmailOnBuyListener;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Symfony\Component\Mime\Email;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\Notifications\SendEmailOnBuyListener
 *
 * This test could probably improve some on the asserting that the template contents are rendered
 * correctly, but since this is already done in another test I'm skipping this for now.
 *
 * @internal
 */
final class SendEmailOnBuyListenerTest extends TesterOfAbstractSendEmailListener
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new SendEmailOnBuyListener(
            $this->notifier,
            $this->htmlConverter,
            $this->emailConfiguration,
            $this->templateConfiguration,
            true
        );

        $this->listener->setTemplateLocation($this->templateLocation);
    }

    /**
     * @covers ::onBuy
     */
    public function testListenerDoesNotActWhenDisabled(): void
    {
        $this->listener = new SendEmailOnBuyListener(
            $this->notifier,
            $this->htmlConverter,
            $this->emailConfiguration,
            $this->templateConfiguration,
            false
        );

        $this->notifier->expects(static::never())->method('send');

        $event = new BuySuccessEvent(new CompletedBuyOrder());
        $this->listener->onBuy($event);
    }

    /**
     * @covers ::onBuy
     */
    public function testAssertThatEmailIsSentOnBuyEvent(): void
    {
        $amountInSatoshis = random_int(10000, 20000);
        $buyOrder = (new CompletedBuyOrder())->setAmountInSatoshis($amountInSatoshis);
        $tag = 't'.random_int(1000, 2000);

        $event = new BuySuccessEvent($buyOrder, $tag);

        $this->notifier
            ->expects(static::once())
            ->method('send')
            ->with(static::callback(function (Email $email) use ($amountInSatoshis) {
                self::assertSame(
                    sprintf(
                        '[%s] %s',
                        $this->subjectPrefix,
                        sprintf(
                            SendEmailOnBuyListener::NOTIFICATION_SUBJECT_LINE,
                            number_format($amountInSatoshis),
                            ucfirst($this->exchange)
                        )
                    ),
                    $email->getSubject()
                );

                return true;
            }))
        ;

        $this->listener->onBuy($event);
    }
}

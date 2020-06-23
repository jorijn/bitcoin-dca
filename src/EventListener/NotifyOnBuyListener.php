<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\EventListener;

use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotifyOnBuyListener
{
    protected MailerInterface $notifier;
    protected string $to;
    protected string $from;
    protected string $subjectPrefix;

    public function __construct(MailerInterface $notifier, string $to, string $from, string $subjectPrefix)
    {
        $this->notifier = $notifier;
        $this->to = $to;
        $this->from = $from;
        $this->subjectPrefix = $subjectPrefix;
    }

    public function onBuy(CompletedBuyOrder $buyOrder): void
    {
        $email = (new Email())
            ->from($this->from)
            ->to($this->to)
            ->subject(sprintf('[%s] %s', $this->subjectPrefix, 'You just saved some sats!'))
            ->text('lorem ipsum etc')
            ->html('<p>See Twig integration for better HTML integration!</p>')
        ;

        $this->notifier->send($email);
    }
}

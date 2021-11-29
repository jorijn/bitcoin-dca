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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramTransport;

abstract class AbstractSendTelegramListener
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

    public function getTransport(): TelegramTransport
    {
        return $this->transport;
    }

    public function getDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function getExchange(): string
    {
        return $this->exchange;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
}

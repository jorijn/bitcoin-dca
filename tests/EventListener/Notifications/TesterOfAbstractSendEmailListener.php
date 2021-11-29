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

use Jorijn\Bitcoin\Dca\EventListener\Notifications\AbstractSendEmailListener;
use Jorijn\Bitcoin\Dca\Model\NotificationEmailConfiguration;
use Jorijn\Bitcoin\Dca\Model\NotificationEmailTemplateInformation;
use League\HTMLToMarkdown\HtmlConverterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @codeCoverageIgnore
 */
abstract class TesterOfAbstractSendEmailListener extends TestCase
{
    /** @var MailerInterface|MockObject */
    protected $notifier;
    /** @var HtmlConverterInterface|MockObject */
    protected $htmlConverter;
    protected string $to;
    protected string $subjectPrefix;
    protected string $from;
    protected NotificationEmailConfiguration $emailConfiguration;
    protected string $exchange;
    protected string $logoLocation;
    protected string $iconLocation;
    protected NotificationEmailTemplateInformation $templateConfiguration;
    protected string $quotesLocation;
    /** @var AbstractSendEmailListener|MockObject */
    protected $listener;
    protected string $templateLocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifier = $this->createMock(MailerInterface::class);
        $this->htmlConverter = $this->createMock(HtmlConverterInterface::class);

        $this->to = random_int(1000, 2000).'@protonmail.com';
        $this->from = random_int(1000, 2000).'@protonmail.com';
        $this->subjectPrefix = '['.random_int(1000, 2000).']';
        $this->emailConfiguration = new NotificationEmailConfiguration($this->to, $this->from, $this->subjectPrefix);

        $this->exchange = 'e'.random_int(1000, 2000);

        $this->logoLocation = realpath(
            __DIR__.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'resources'.\DIRECTORY_SEPARATOR.'images'.\DIRECTORY_SEPARATOR
        ).\DIRECTORY_SEPARATOR.'logo-small.png';

        $this->iconLocation = realpath(
            __DIR__.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'..'.\DIRECTORY_SEPARATOR.'resources'.\DIRECTORY_SEPARATOR.'images'.\DIRECTORY_SEPARATOR
        ).\DIRECTORY_SEPARATOR.'github-logo-colored.png';

        $this->quotesLocation = tempnam(sys_get_temp_dir(), 'quotes');
        $this->templateLocation = tempnam(sys_get_temp_dir(), 'quotes');
        $this->templateConfiguration = new NotificationEmailTemplateInformation(
            $this->exchange,
            $this->logoLocation,
            $this->iconLocation,
            $this->quotesLocation
        );

        file_put_contents($this->quotesLocation, '{}');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unlink($this->quotesLocation);
    }
}

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

use JetBrains\PhpStorm\ArrayShape;
use Jorijn\Bitcoin\Dca\Exception\UnableToGetRandomQuoteException;
use Jorijn\Bitcoin\Dca\Model\NotificationEmailConfiguration;
use Jorijn\Bitcoin\Dca\Model\NotificationEmailTemplateInformation;
use Jorijn\Bitcoin\Dca\Model\Quote;
use League\HTMLToMarkdown\HtmlConverterInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

abstract class AbstractSendEmailListener
{
    protected MailerInterface $notifier;
    protected HtmlConverterInterface $htmlConverter;
    protected NotificationEmailConfiguration $emailConfiguration;
    protected NotificationEmailTemplateInformation $templateInformation;
    protected string $templateLocation;
    protected bool $isEnabled;

    public function __construct(
        MailerInterface $notifier,
        HtmlConverterInterface $htmlConverter,
        NotificationEmailConfiguration $emailConfiguration,
        NotificationEmailTemplateInformation $templateInformation,
        bool $isEnabled = false
    ) {
        $this->notifier = $notifier;
        $this->htmlConverter = $htmlConverter;
        $this->isEnabled = $isEnabled;
        $this->emailConfiguration = $emailConfiguration;
        $this->templateInformation = $templateInformation;
    }

    public function setTemplateLocation(string $templateLocation): void
    {
        $this->templateLocation = $templateLocation;
    }

    public function getRandomQuote(): ?Quote
    {
        try {
            $quotes = json_decode(file_get_contents($this->templateInformation->getQuotesLocation()), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new UnableToGetRandomQuoteException($e->getMessage(), $e->getCode(), $e);
        }

        if (!\is_array($quotes) || empty($quotes) || 2 !== \count($quotes[0])) {
            return null;
        }

        ['quote' => $quote, 'author' => $quoteAuthor] = $quotes[array_rand($quotes)];

        return new Quote($quote, $quoteAuthor);
    }

    #[ArrayShape(['quote' => "string", 'quoteAuthor' => "string", 'exchange' => "string"])]
    public function getTemplateVariables(): array
    {
        $quote = $this->getRandomQuote();

        return [
            'quote' => $quote instanceof Quote ? $quote->getQuote() : '',
            'quoteAuthor' => $quote instanceof Quote ? $quote->getAuthor() : '',
            'exchange' => $this->templateInformation->getExchange(),
        ];
    }

    public function renderTemplate(string $templateLocation, array $templateVariables): string
    {
        extract($templateVariables, EXTR_OVERWRITE);
        ob_start();

        include $templateLocation;

        return ob_get_clean();
    }

    public function createEmail(): Email
    {
        return (new Email())
            ->from($this->emailConfiguration->getFrom())
            ->to($this->emailConfiguration->getTo())
            ->embedFromPath($this->templateInformation->getLogoLocation(), 'logo')
            ->embedFromPath($this->templateInformation->getIconLocation(), 'github-icon')
        ;
    }
}

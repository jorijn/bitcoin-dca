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
use Jorijn\Bitcoin\Dca\Model\Quote;
use League\HTMLToMarkdown\HtmlConverterInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

abstract class AbstractSendEmailListener
{
    protected MailerInterface $notifier;
    protected string $to;
    protected string $from;
    protected string $subjectPrefix;
    protected string $templateLocation;
    protected HtmlConverterInterface $htmlConverter;
    protected string $logoLocation;
    protected string $iconLocation;
    protected string $exchange;
    protected string $quotesLocation;

    public function __construct(
        MailerInterface $notifier,
        HtmlConverterInterface $htmlConverter,
        string $to,
        string $from,
        string $subjectPrefix,
        string $exchange,
        string $logoLocation,
        string $iconLocation,
        string $quotesLocation
    ) {
        $this->notifier = $notifier;
        $this->to = $to;
        $this->from = $from;
        $this->subjectPrefix = $subjectPrefix;
        $this->htmlConverter = $htmlConverter;
        $this->logoLocation = $logoLocation;
        $this->iconLocation = $iconLocation;
        $this->exchange = ucfirst($exchange);
        $this->quotesLocation = $quotesLocation;
    }

    public function setTemplateLocation(string $templateLocation): void
    {
        $this->templateLocation = $templateLocation;
    }

    protected function getRandomQuote(): Quote
    {
        try {
            $quotes = json_decode(file_get_contents($this->quotesLocation), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new UnableToGetRandomQuoteException($e->getMessage(), $e->getCode(), $e);
        }

        ['quote' => $quote, 'author' => $quoteAuthor] = $quotes[array_rand($quotes)];

        return new Quote($quote, $quoteAuthor);
    }

    #[ArrayShape(['quote' => "string", 'quoteAuthor' => "string", 'exchange' => "string"])]
    protected function getTemplateVariables(): array
    {
        $quote = $this->getRandomQuote();

        return [
            'quote' => $quote->getQuote(),
            'quoteAuthor' => $quote->getAuthor(),
            'exchange' => $this->exchange,
        ];
    }

    protected function renderTemplate(string $templateLocation, array $templateVariables): string
    {
        if (!$this->templateLocation) {
            throw new \InvalidArgumentException('template location has not been set yet');
        }

        extract($templateVariables, EXTR_OVERWRITE);
        ob_start();

        /** @noinspection PhpIncludeInspection */
        include $templateLocation;

        return ob_get_clean();
    }

    protected function createEmail(): Email
    {
        return (new Email())
            ->from($this->from)
            ->to($this->to)
            ->embedFromPath($this->logoLocation, 'logo')
            ->embedFromPath($this->iconLocation, 'github-icon')
        ;
    }
}

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
use Jorijn\Bitcoin\Dca\Exception\UnableToGetRandomQuoteException;
use Jorijn\Bitcoin\Dca\Model\Quote;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\Notifications\AbstractSendEmailListener
 * @covers ::__construct
 *
 * @internal
 */
final class AbstractSendEmailListenerTest extends TesterOfAbstractSendEmailListener
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = $this->getMockForAbstractClass(
            AbstractSendEmailListener::class,
            [
                $this->notifier,
                $this->htmlConverter,
                $this->emailConfiguration,
                $this->templateConfiguration,
            ]
        );
    }

    /**
     * @covers ::getRandomQuote
     *
     * @throws \JsonException
     */
    public function testGettingRandomQuote(): void
    {
        $quotes = ['1', '2'];
        $authors = ['3', '4'];

        file_put_contents(
            $this->quotesLocation,
            json_encode(
                [['quote' => $quotes[0], 'author' => $authors[0]], ['quote' => $quotes[1], 'author' => $authors[1]]],
                JSON_THROW_ON_ERROR
            )
        );

        $quote = $this->listener->getRandomQuote();
        static::assertInstanceOf(Quote::class, $quote);
        static::assertContains($quote->getQuote(), $quotes);
        static::assertContains($quote->getAuthor(), $authors);
    }

    /**
     * @covers ::getRandomQuote
     */
    public function testGettingRandomQuoteFailsCorruptJSON(): void
    {
        file_put_contents(
            $this->quotesLocation,
            '{"false}'
        );

        $this->expectException(UnableToGetRandomQuoteException::class);
        $this->listener->getRandomQuote();
    }

    /**
     * @covers ::getRandomQuote
     */
    public function testGettingRandomQuoteFailsEmptyFile(): void
    {
        file_put_contents(
            $this->quotesLocation,
            '{}'
        );

        $quote = $this->listener->getRandomQuote();
        static::assertNull($quote);
    }

    /**
     * @covers ::getTemplateVariables
     */
    public function testGettingOfTemplateVariables(): void
    {
        $quote = 'q'.random_int(1000, 2000);
        $quoteAuthor = 'qa'.random_int(1000, 2000);

        file_put_contents(
            $this->quotesLocation,
            json_encode(
                [['quote' => $quote, 'author' => $quoteAuthor]],
                JSON_THROW_ON_ERROR
            )
        );

        $templateVariables = $this->listener->getTemplateVariables();
        static::assertArrayHasKey('quote', $templateVariables);
        static::assertSame($quote, $templateVariables['quote']);
        static::assertArrayHasKey('quoteAuthor', $templateVariables);
        static::assertSame($quoteAuthor, $templateVariables['quoteAuthor']);
        static::assertArrayHasKey('exchange', $templateVariables);
        static::assertSame($this->exchange, $templateVariables['exchange']);
    }

    /**
     * @covers ::renderTemplate
     */
    public function testRenderingOfTemplate(): void
    {
        $tplFile = tempnam(sys_get_temp_dir(), 'dcatpl');
        $randomIdentifier = 'ri'.random_int(1000, 2000);

        try {
            file_put_contents($tplFile, '<?php echo $randomIdentifierVar; ?>');

            $renderedContents = $this->listener->renderTemplate($tplFile, ['randomIdentifierVar' => $randomIdentifier]);
            static::assertSame($randomIdentifier, $renderedContents);
        } finally {
            unlink($tplFile);
        }
    }

    /**
     * @covers ::createEmail
     */
    public function testCreationOfEmailObject(): void
    {
        $email = $this->listener->createEmail();

        static::assertSame($this->from, $email->getFrom()[0]->getAddress());
        static::assertSame($this->to, $email->getTo()[0]->getAddress());

        $v = $email->getAttachments();
        static::assertSame('image/png disposition: inline filename: logo', $v[0]->asDebugString());
        static::assertSame('image/png disposition: inline filename: github-icon', $v[1]->asDebugString());
    }

    /**
     * @covers ::getTemplateLocation
     * @covers ::setTemplateLocation
     */
    public function testTemplateLocationSetter(): void
    {
        $location = 'l'.random_int(1000, 2000);
        $this->listener->setTemplateLocation($location);

        static::assertSame($this->listener->getTemplateLocation(), $location);
    }
}

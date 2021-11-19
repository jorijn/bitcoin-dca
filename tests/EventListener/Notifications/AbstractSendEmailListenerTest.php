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
use Jorijn\Bitcoin\Dca\Model\NotificationEmailConfiguration;
use Jorijn\Bitcoin\Dca\Model\NotificationEmailTemplateInformation;
use Jorijn\Bitcoin\Dca\Model\Quote;
use League\HTMLToMarkdown\HtmlConverterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\Notifications\AbstractSendEmailListener
 * @covers ::__construct
 *
 * @internal
 */
final class AbstractSendEmailListenerTest extends TestCase
{
    /** @var MailerInterface|MockObject */
    private $notifier;
    /** @var HtmlConverterInterface|MockObject */
    private $htmlConverter;
    private string $to;
    private string $subjectPrefix;
    private string $from;
    private NotificationEmailConfiguration $emailConfiguration;
    private string $exchange;
    private string $logoLocation;
    private string $iconLocation;
    private NotificationEmailTemplateInformation $templateConfiguration;
    private string $quotesLocation;
    /** @var AbstractSendEmailListener|MockObject */
    private $listener;

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
        $this->templateConfiguration = new NotificationEmailTemplateInformation(
            $this->exchange,
            $this->logoLocation,
            $this->iconLocation,
            $this->quotesLocation
        );

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

    protected function tearDown(): void
    {
        parent::tearDown(); // TODO: Change the autogenerated stub

        unlink($this->quotesLocation);
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
        $quote = 'q'.mt_rand();
        $quoteAuthor = 'qa'.mt_rand();

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
        $randomIdentifier = 'ri'.mt_rand();

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
     * @covers ::setTemplateLocation
     */
    public function testTemplateLocationSetter(): void
    {
        $location = 'l'.mt_rand();
        $this->listener->setTemplateLocation($location);

        $setLocation = \Closure::bind(
            fn () => $this->templateLocation,
            $this->listener,
            $this->listener
        )();

        static::assertSame($location, $setLocation);
    }
}
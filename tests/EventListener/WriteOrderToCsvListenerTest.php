<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\EventListener;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\EventListener\WriteOrderToCsvListener;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\WriteOrderToCsvListener
 * @covers ::__construct
 * @covers ::onSuccessfulBuy
 *
 * @internal
 */
final class WriteOrderToCsvListenerTest extends TestCase
{
    private string $temporaryFile;
    private TestLogger $logger;
    /** @var MockObject|SerializerInterface */
    private $serializer;
    private WriteOrderToCsvListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryFile = tempnam(sys_get_temp_dir(), 'dca_csv');
        $this->logger = new TestLogger();
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->listener = new WriteOrderToCsvListener($this->serializer, $this->logger, $this->temporaryFile);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->temporaryFile)) {
            unlink($this->temporaryFile);
        }
    }

    public function testListenerIsDisabled(): void
    {
        $this->listener = new WriteOrderToCsvListener($this->serializer, $this->logger, null);

        $event = new BuySuccessEvent(new CompletedBuyOrder());
        $this->listener->onSuccessfulBuy($event);

        static::assertFalse($this->logger->hasErrorRecords());
        static::assertSame(0, filesize($this->temporaryFile));
    }

    public function testFileDoesNotExistsYet(): void
    {
        unlink($this->temporaryFile);

        $order = new CompletedBuyOrder();
        $mockedCsvOutput = 'foo,bar';

        $this->serializer
            ->expects(static::once())
            ->method('serialize')
            ->with($order, 'csv', [CsvEncoder::NO_HEADERS_KEY => false])
            ->willReturn($mockedCsvOutput)
        ;

        $event = new BuySuccessEvent($order);
        $this->listener->onSuccessfulBuy($event);

        static::assertFalse($this->logger->hasErrorRecords());
        static::assertSame($mockedCsvOutput, file_get_contents($this->temporaryFile));
    }

    public function testFileIsEmpty(): void
    {
        $order = new CompletedBuyOrder();
        $mockedCsvOutput = 'bar,baz';

        $this->serializer
            ->expects(static::once())
            ->method('serialize')
            ->with($order, 'csv', [CsvEncoder::NO_HEADERS_KEY => false])
            ->willReturn($mockedCsvOutput)
        ;

        $event = new BuySuccessEvent($order);
        $this->listener->onSuccessfulBuy($event);

        static::assertFalse($this->logger->hasErrorRecords());
        static::assertSame($mockedCsvOutput, file_get_contents($this->temporaryFile));
    }

    public function testExceptionsAreHandled(): void
    {
        $order = new CompletedBuyOrder();

        $this->serializer
            ->expects(static::once())
            ->method('serialize')
            ->with($order, 'csv', [CsvEncoder::NO_HEADERS_KEY => false])
            ->willThrowException(new \Exception('broken'))
        ;

        $event = new BuySuccessEvent($order);
        $this->listener->onSuccessfulBuy($event);

        static::assertTrue($this->logger->hasError('unable to write order to file'));
        static::assertSame(0, filesize($this->temporaryFile));
    }

    public function testOrderIsWrittenToExistingCsv(): void
    {
        $preExistingContent = 'already,exists'.PHP_EOL;
        file_put_contents($this->temporaryFile, $preExistingContent);

        $order = new CompletedBuyOrder();
        $mockedCsvOutput = 'foo,bar';

        $this->serializer
            ->expects(static::once())
            ->method('serialize')
            ->with($order, 'csv', [CsvEncoder::NO_HEADERS_KEY => true])
            ->willReturn($mockedCsvOutput)
        ;

        $event = new BuySuccessEvent($order);
        $this->listener->onSuccessfulBuy($event);

        static::assertFalse($this->logger->hasErrorRecords());
        static::assertSame($preExistingContent.$mockedCsvOutput, file_get_contents($this->temporaryFile));
    }
}

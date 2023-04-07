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

namespace Tests\Jorijn\Bitcoin\Dca\EventListener;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\EventListener\WriteOrderToCsvListener;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\WriteOrderToCsvListener
 *
 * @covers ::__construct
 * @covers ::onSuccessfulBuy
 *
 * @internal
 */
final class WriteOrderToCsvListenerTest extends TestCase
{
    private string $temporaryFile;

    private \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\Serializer\SerializerInterface $serializer;
    private WriteOrderToCsvListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryFile = tempnam(sys_get_temp_dir(), 'dca_csv');
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->listener = new WriteOrderToCsvListener(
            $this->serializer,
            $this->createMock(LoggerInterface::class),
            $this->temporaryFile
        );
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
        $this->listener = new WriteOrderToCsvListener(
            $this->serializer,
            $this->createMock(LoggerInterface::class),
            null
        );

        $buySuccessEvent = new BuySuccessEvent(new CompletedBuyOrder());
        $this->listener->onSuccessfulBuy($buySuccessEvent);

        static::assertSame(0, filesize($this->temporaryFile));
    }

    public function testFileDoesNotExistsYet(): void
    {
        unlink($this->temporaryFile);

        $completedBuyOrder = new CompletedBuyOrder();
        $mockedCsvOutput = 'foo,bar';

        $this->serializer
            ->expects(static::once())
            ->method('serialize')
            ->with($completedBuyOrder, 'csv', [CsvEncoder::NO_HEADERS_KEY => false])
            ->willReturn($mockedCsvOutput)
        ;

        $buySuccessEvent = new BuySuccessEvent($completedBuyOrder);
        $this->listener->onSuccessfulBuy($buySuccessEvent);

        static::assertSame($mockedCsvOutput, file_get_contents($this->temporaryFile));
    }

    public function testFileIsEmpty(): void
    {
        $completedBuyOrder = new CompletedBuyOrder();
        $mockedCsvOutput = 'bar,baz';

        $this->serializer
            ->expects(static::once())
            ->method('serialize')
            ->with($completedBuyOrder, 'csv', [CsvEncoder::NO_HEADERS_KEY => false])
            ->willReturn($mockedCsvOutput)
        ;

        $buySuccessEvent = new BuySuccessEvent($completedBuyOrder);
        $this->listener->onSuccessfulBuy($buySuccessEvent);

        static::assertSame($mockedCsvOutput, file_get_contents($this->temporaryFile));
    }

    public function testExceptionsAreHandled(): void
    {
        $completedBuyOrder = new CompletedBuyOrder();

        $this->serializer
            ->expects(static::once())
            ->method('serialize')
            ->with($completedBuyOrder, 'csv', [CsvEncoder::NO_HEADERS_KEY => false])
            ->willThrowException(new \Exception('broken'))
        ;

        $buySuccessEvent = new BuySuccessEvent($completedBuyOrder);
        $this->listener->onSuccessfulBuy($buySuccessEvent);

        static::assertSame(0, filesize($this->temporaryFile));
    }

    public function testOrderIsWrittenToExistingCsv(): void
    {
        $preExistingContent = 'already,exists'.PHP_EOL;
        file_put_contents($this->temporaryFile, $preExistingContent);

        $completedBuyOrder = new CompletedBuyOrder();
        $mockedCsvOutput = 'foo,bar';

        $this->serializer
            ->expects(static::once())
            ->method('serialize')
            ->with($completedBuyOrder, 'csv', [CsvEncoder::NO_HEADERS_KEY => true])
            ->willReturn($mockedCsvOutput)
        ;

        $buySuccessEvent = new BuySuccessEvent($completedBuyOrder);
        $this->listener->onSuccessfulBuy($buySuccessEvent);

        static::assertSame($preExistingContent.$mockedCsvOutput, file_get_contents($this->temporaryFile));
    }
}

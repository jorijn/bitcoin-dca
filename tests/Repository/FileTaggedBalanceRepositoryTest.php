<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Repository;

use Jorijn\Bl3pDca\Repository\FileTaggedBalanceRepository;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Repository\FileTaggedBalanceRepository
 * @covers ::__construct
 */
class FileTaggedBalanceRepositoryTest extends TestCase
{
    private string $file;
    private FileTaggedBalanceRepository $repository;

    /**
     * @covers ::setTagBalance
     * @covers ::read
     * @covers ::write
     */
    public function testSetTagBalance(): void
    {
        $this->repository->setTagBalance('test1', 500);
        $this->repository->setTagBalance('test2', 1000);

        $this->assertFileContents(['test1' => 500, 'test2' => 1000]);
    }

    /**
     * @covers ::decreaseTagBalance
     * @covers ::read
     * @covers ::write
     */
    public function testDecreaseTagBalance(): void
    {
        $this->repository->setTagBalance('test1', 500);
        $this->repository->setTagBalance('test2', 1000);

        $this->repository->decreaseTagBalance('test1', 250);
        $this->repository->decreaseTagBalance('test2', 200);
        $this->repository->decreaseTagBalance('test3', 100);

        $this->assertFileContents(['test1' => 250, 'test2' => 800, 'test3' => -100]);
    }

    /**
     * @covers ::getTagBalance
     * @covers ::read
     * @covers ::write
     */
    public function testGetTagBalance(): void
    {
        $this->repository->setTagBalance('test1', 500);
        $this->repository->setTagBalance('test2', 1000);

        $this->assertFileContents(['test1' => 500, 'test2' => 1000]);

        self::assertSame(500, $this->repository->getTagBalance('test1'));
        self::assertSame(1000, $this->repository->getTagBalance('test2'));
    }

    /**
     * @covers ::increaseTagBalance
     * @covers ::read
     * @covers ::write
     */
    public function testIncreaseTagBalance(): void
    {
        $this->repository->setTagBalance('test1', 500);
        $this->repository->setTagBalance('test2', 1000);

        $this->repository->increaseTagBalance('test1', 250);
        $this->repository->increaseTagBalance('test2', 200);
        $this->repository->increaseTagBalance('test3', 100);

        $this->assertFileContents(['test1' => 750, 'test2' => 1200, 'test3' => 100]);
    }

    /**
     * @covers ::read
     */
    public function testNonExistingFileReturnsEmpty(): void
    {
        $this->repository = new FileTaggedBalanceRepository('file'.mt_rand());
        self::assertSame(0, $this->repository->getTagBalance('test'));
    }

    /**
     * @covers ::read
     */
    public function testCorruptJsonGetsReset(): void
    {
        file_put_contents($this->file, 'cor,,ru..ptjson'.mt_rand());
        self::assertSame(0, $this->repository->getTagBalance('test'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = __CLASS__.'.db';
        $this->repository = new FileTaggedBalanceRepository($this->file);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }

    protected function assertFileContents(array $data): void
    {
        $testedData = json_decode(file_get_contents($this->file), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($testedData);
        self::assertSame($data, $testedData);
    }
}

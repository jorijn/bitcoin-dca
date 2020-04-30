<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Repository;

use Jorijn\Bl3pDca\Repository\JsonFileTaggedIntegerRepository;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Repository\JsonFileTaggedIntegerRepository
 * @covers ::__construct
 */
class JsonFileTaggedIntegerRepositoryTest extends TestCase
{
    private string $file;
    private JsonFileTaggedIntegerRepository $repository;

    /**
     * @covers ::set
     * @covers ::read
     * @covers ::write
     */
    public function testSetTagBalance(): void
    {
        $this->repository->set('test1', 500);
        $this->repository->set('test2', 1000);

        $this->assertFileContents(['test1' => 500, 'test2' => 1000]);
    }

    /**
     * @covers ::decrease
     * @covers ::read
     * @covers ::write
     */
    public function testDecreaseTagBalance(): void
    {
        $this->repository->set('test1', 500);
        $this->repository->set('test2', 1000);

        $this->repository->decrease('test1', 250);
        $this->repository->decrease('test2', 200);
        $this->repository->decrease('test3', 100);

        $this->assertFileContents(['test1' => 250, 'test2' => 800, 'test3' => -100]);
    }

    /**
     * @covers ::get
     * @covers ::read
     * @covers ::write
     */
    public function testGetTagBalance(): void
    {
        $this->repository->set('test1', 500);
        $this->repository->set('test2', 1000);

        $this->assertFileContents(['test1' => 500, 'test2' => 1000]);

        self::assertSame(500, $this->repository->get('test1'));
        self::assertSame(1000, $this->repository->get('test2'));
    }

    /**
     * @covers ::increase
     * @covers ::read
     * @covers ::write
     */
    public function testIncreaseTagBalance(): void
    {
        $this->repository->set('test1', 500);
        $this->repository->set('test2', 1000);

        $this->repository->increase('test1', 250);
        $this->repository->increase('test2', 200);
        $this->repository->increase('test3', 100);

        $this->assertFileContents(['test1' => 750, 'test2' => 1200, 'test3' => 100]);
    }

    /**
     * @covers ::read
     */
    public function testNonExistingFileReturnsEmpty(): void
    {
        $this->repository = new JsonFileTaggedIntegerRepository('file'.mt_rand());
        self::assertSame(0, $this->repository->get('test'));
    }

    /**
     * @covers ::read
     */
    public function testCorruptJsonGetsReset(): void
    {
        file_put_contents($this->file, 'cor,,ru..ptjson'.mt_rand());
        self::assertSame(0, $this->repository->get('test'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = __CLASS__.'.db';
        $this->repository = new JsonFileTaggedIntegerRepository($this->file);
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

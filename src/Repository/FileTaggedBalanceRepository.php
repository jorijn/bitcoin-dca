<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Repository;

use JsonException;

class FileTaggedBalanceRepository implements TaggedBalanceRepositoryInterface
{
    protected string $fileLocation;

    public function __construct(string $fileLocation)
    {
        $this->fileLocation = $fileLocation;
    }

    public function increaseTagBalance(string $tag, int $satoshis): void
    {
        $data = $this->read();

        $data[$tag] ??= 0;
        $data[$tag] += $satoshis;

        $this->write($data);
    }

    public function decreaseTagBalance(string $tag, int $satoshis): void
    {
        $data = $this->read();

        $data[$tag] ??= 0;
        $data[$tag] -= $satoshis;

        $this->write($data);
    }

    public function setTagBalance(string $tag, int $satoshis): void
    {
        $data = $this->read();

        $data[$tag] = $satoshis;

        $this->write($data);
    }

    public function getTagBalance(string $tag): int
    {
        $data = $this->read();

        return $data[$tag] ?? 0;
    }

    /**
     * @param array $data
     *
     * @throws JsonException
     */
    protected function write($data = []): void
    {
        file_put_contents($this->fileLocation, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, 512));
    }

    protected function read(): array
    {
        if (file_exists($this->fileLocation)) {
            try {
                return json_decode(file_get_contents($this->fileLocation), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                return [];
            }
        }

        return [];
    }
}

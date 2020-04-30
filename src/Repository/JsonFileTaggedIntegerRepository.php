<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Repository;

use JsonException;

class JsonFileTaggedIntegerRepository implements TaggedIntegerRepositoryInterface
{
    protected string $fileLocation;

    public function __construct(string $fileLocation)
    {
        $this->fileLocation = $fileLocation;
    }

    public function increase(string $tag, int $value = 1): void
    {
        $data = $this->read();

        $data[$tag] ??= 0;
        $data[$tag] += $value;

        $this->write($data);
    }

    public function decrease(string $tag, int $value = 1): void
    {
        $data = $this->read();

        $data[$tag] ??= 0;
        $data[$tag] -= $value;

        $this->write($data);
    }

    public function set(string $tag, int $value): void
    {
        $data = $this->read();

        $data[$tag] = $value;

        $this->write($data);
    }

    public function get(string $tag): int
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

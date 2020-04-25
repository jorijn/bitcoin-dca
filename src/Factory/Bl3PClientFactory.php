<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Factory;

use InvalidArgumentException;
use Jorijn\Bl3pDca\Client\Bl3PClient;
use Jorijn\Bl3pDca\Client\Bl3PClientInterface;

class Bl3PClientFactory
{
    public function createApi(): Bl3PClientInterface
    {
        if (!isset($_ENV['BL3P_API_URL'], $_ENV['BL3P_PUBLIC_KEY'], $_ENV['BL3P_PRIVATE_KEY'])) {
            throw new InvalidArgumentException('Incomplete configuration, missing BL3P_API_URL, BL3P_PUBLIC_KEY or BL3P_PRIVATE_KEY');
        }

        return new Bl3PClient($_ENV['BL3P_API_URL'], $_ENV['BL3P_PUBLIC_KEY'], $_ENV['BL3P_PRIVATE_KEY']);
    }
}

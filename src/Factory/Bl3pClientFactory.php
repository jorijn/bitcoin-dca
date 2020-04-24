<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Factory;

use Jorijn\Bl3pDca\Client\Bl3PClient;

class Bl3PClientFactory
{
    public function createApi(): Bl3PClient
    {
        return new Bl3PClient($_ENV['Bl3P_API_URL'], $_ENV['BL3P_PUBLIC_KEY'], $_ENV['BL3P_PRIVATE_KEY']);
    }
}

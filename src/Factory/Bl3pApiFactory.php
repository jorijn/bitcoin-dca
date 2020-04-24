<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Factory;

use Jorijn\Bl3pDca\Api\Bl3pApi;

class Bl3pApiFactory
{
    public function createApi(): Bl3pApi
    {
        return new Bl3pApi($_ENV['Bl3P_API_URL'], $_ENV['BL3P_PUBLIC_KEY'], $_ENV['BL3P_PRIVATE_KEY']);
    }
}

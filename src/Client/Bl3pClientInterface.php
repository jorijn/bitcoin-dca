<?php

declare(strict_types=1);

namespace Jorijn\Bl3pDca\Client;

interface Bl3pClientInterface
{
    /**
     * To make a call to BL3P API.
     *
     * @param string $path   path to call
     * @param array  $params parameters to add to the call
     *
     * @throws \Exception
     *
     * @return array result of call
     */
    public function apiCall($path, $params = []): array;
}

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

namespace Jorijn\Bitcoin\Dca\Client;

use Exception;

interface Bl3pClientInterface
{
    /**
     * To make a call to BL3P API.
     *
     * @param string $path       path to call
     * @param array  $parameters parameters to add to the call
     *
     * @throws Exception
     *
     * @return array result of call
     */
    public function apiCall($path, $parameters = []): array;
}

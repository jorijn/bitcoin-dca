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

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv') && file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__).'/.env');
}

// set the default location for the external derivation tool
if (false === getenv('XPUB_PYTHON_CLI') && file_exists('/app/resources/xpub_derive/main.py')) {
    putenv('XPUB_PYTHON_CLI=/usr/bin/python3 /app/resources/xpub_derive/main.py');
}

<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv') && file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__).'/.env');
}

// set the default location for the external derivation tool
if (false === getenv('XPUB_PYTHON_CLI') && file_exists('/app/resources/xpub_derive/main.py')) {
    putenv('XPUB_PYTHON_CLI=/usr/bin/python3 /app/resources/xpub_derive/main.py');
}

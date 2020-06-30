<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// set the default location for the external derivation tool
if (!isset($_SERVER['XPUB_PYTHON_CLI'])) {
    $_SERVER['XPUB_PYTHON_CLI'] = '/usr/bin/python3 /app/resources/xpub_derive/main.py';
}

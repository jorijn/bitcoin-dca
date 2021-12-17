<?php

namespace Features\Jorijn\Bitcoin\Dca;

use Behat\Behat\Context\Context;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;

class ApplicationContext implements Context
{
    /** @BeforeSuite */
    public static function setupEnvironment(BeforeSuiteScope $scope): void
    {
        // setting the environment to `test` allows Dotenv to load the specific
        // integration testing configuration
        $_SERVER['ENV'] = 'test';
    }
}

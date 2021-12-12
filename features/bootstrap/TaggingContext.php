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

namespace Features\Jorijn\Bitcoin\Dca;

use Behat\Behat\Context\Context;
use Jorijn\Bitcoin\Dca\Repository\TaggedIntegerRepositoryInterface;
use Symfony\Component\Console\Application;

class TaggingContext implements Context
{
    public function __construct(
        protected Application $application,
        protected TaggedIntegerRepositoryInterface $repository
    ) {
    }

    /**
     * @Given /^there is no information for tag "([^"]*)" yet$/
     */
    public function thereIsNoInformationForTagYet(string $tag): void
    {
        \assert(0 === $this->repository->get($tag));
    }

    /**
     * @When /^the current Bitcoin price is (\d+) dollar$/
     */
    public function theCurrentBitcoinPriceIsDollar(int $price): void
    {
        throw new \Behat\Behat\Tester\Exception\PendingException();
    }

    /**
     * @Given /^I buy (\d+) dollar worth of Bitcoin for tag "([^"]*)"$/
     */
    public function iBuyDollarWorthOfBitcoinForTag(int $dollars, string $tag): void
    {
        throw new \Behat\Behat\Tester\Exception\PendingException();
    }

    /**
     * @Then /^I expect the balance of tag "([^"]*)" to be (\d+) satoshis/
     */
    public function iExpectTheBalanceOfTagToBeSatoshis(string $tag, int $satoshis): void
    {
        throw new \Behat\Behat\Tester\Exception\PendingException();
    }

    /**
     * @Given /^the balance for tag "([^"]*)" is (\d+) satoshis$/
     */
    public function theBalanceForTagIsSatoshis(string $tag, int $satoshis): void
    {
        throw new \Behat\Behat\Tester\Exception\PendingException();
    }
}

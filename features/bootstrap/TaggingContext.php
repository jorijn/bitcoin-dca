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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

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
        $this->theBalanceForTagIsSatoshis($tag, 0);
    }

    /**
     * @Given /^I buy (\d+) dollar worth of Bitcoin for tag "([^"]*)"$/
     */
    public function iBuyDollarWorthOfBitcoinForTag(int $dollars, string $tag): void
    {
        $command = $this->application->find('buy');
        $inputArguments = new ArrayInput(
            [
                'amount' => $dollars,
                '--yes' => true,
                '--tag' => $tag,
            ]
        );

        $output = new BufferedOutput();
        $exitStatus = $command->run($inputArguments, $output);

        \assert(
            0 === $exitStatus,
            sprintf('exit code is not 0, actual: %d (output: %s)', $exitStatus, $output->fetch())
        );
    }

    /**
     * @When /^I withdraw the entire balance for tag "([^"]*)"$/
     */
    public function iWithdrawTheEntireBalanceForTag(string $tag): void
    {
        $command = $this->application->find('withdraw');
        $inputArguments = new ArrayInput(
            [
                '--all' => true,
                '--yes' => true,
                '--tag' => $tag,
            ]
        );

        $output = new BufferedOutput();
        $exitStatus = $command->run($inputArguments, $output);

        \assert(
            0 === $exitStatus,
            sprintf('exit code is not 0, actual: %d (output: %s)', $exitStatus, $output->fetch())
        );
    }

    /**
     * @Then /^I expect the balance of tag "([^"]*)" to be (\d+) satoshis/
     */
    public function iExpectTheBalanceOfTagToBeSatoshis(string $tag, int $satoshis): void
    {
        $balance = $this->repository->get($tag);
        \assert($satoshis === $balance, sprintf('balance is not %d, actual: %d', $satoshis, $balance));
    }

    /**
     * @Given /^the balance for tag "([^"]*)" is (\d+) satoshis$/
     * @Given /^the balance for tag "([^"]*)" is still (\d+) satoshis$/
     */
    public function theBalanceForTagIsSatoshis(string $tag, int $satoshis): void
    {
        $this->repository->set($tag, $satoshis);
    }

    /**
     * @When /^I withdraw the entire balance for tag "([^"]*)" and it fails$/
     */
    public function iWithdrawTheEntireBalanceForTagAndItFails(string $tag): void
    {
        $command = $this->application->find('withdraw');
        $inputArguments = new ArrayInput(
            [
                '--all' => true,
                '--yes' => true,
                '--tag' => $tag,
            ]
        );

        $exceptionThrown = false;

        try {
            $command->run($inputArguments, new NullOutput());
        } catch (\Throwable) {
            $exceptionThrown = true;
        }

        \assert(true === $exceptionThrown, 'withdraw did not fail');
    }
}

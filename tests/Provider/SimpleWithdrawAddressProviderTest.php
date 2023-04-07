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

namespace Tests\Jorijn\Bitcoin\Dca\Provider;

use Jorijn\Bitcoin\Dca\Provider\SimpleWithdrawAddressProvider;
use Jorijn\Bitcoin\Dca\Validator\ValidationException;
use Jorijn\Bitcoin\Dca\Validator\ValidationInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Provider\SimpleWithdrawAddressProvider
 *
 * @covers ::__construct
 *
 * @internal
 */
final class SimpleWithdrawAddressProviderTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject|\Jorijn\Bitcoin\Dca\Validator\ValidationInterface $validation;

    private SimpleWithdrawAddressProvider $provider;
    private string $configuredAddress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuredAddress = 'ca'.random_int(1000, 2000);
        $this->validation = $this->createMock(ValidationInterface::class);
        $this->provider = new SimpleWithdrawAddressProvider($this->validation, $this->configuredAddress);
    }

    /**
     * @covers ::provide
     */
    public function testExpectExceptionWhenValidationFails(): void
    {
        $validationException = new ValidationException('error'.random_int(1000, 2000));

        $this->validation
            ->expects(static::once())
            ->method('validate')
            ->with($this->configuredAddress)
            ->willThrowException($validationException)
        ;

        $this->expectExceptionObject($validationException);

        $this->provider->provide();
    }

    /**
     * @covers ::provide
     */
    public function testExpectAddressToBeReturnedWhenValid(): void
    {
        $this->validation
            ->expects(static::once())
            ->method('validate')
            ->with($this->configuredAddress)
        ;

        static::assertSame($this->configuredAddress, $this->provider->provide());
    }
}

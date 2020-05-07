<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Validator;

use BitWasp\Bitcoin\Address\AddressCreator;
use Exception;
use Jorijn\Bl3pDca\Validator\BitcoinAddressValidator;
use Jorijn\Bl3pDca\Validator\BitcoinAddressValidatorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Validator\BitcoinAddressValidator
 * @covers ::__construct
 *
 * @internal
 */
final class BitcoinAddressValidatorTest extends TestCase
{
    /** @var AddressCreator|MockObject */
    private $addressCreator;
    /** @var BitcoinAddressValidator */
    private BitcoinAddressValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addressCreator = $this->createMock(AddressCreator::class);
        $this->validator = new BitcoinAddressValidator($this->addressCreator);
    }

    /**
     * @covers ::validate
     */
    public function testExpectFailureOnEmptyInput(): void
    {
        $this->expectException(BitcoinAddressValidatorException::class);
        $this->validator->validate('');
    }

    /**
     * @covers ::validate
     */
    public function testExpectFailureOnAddressCreatorFailure(): void
    {
        $address = 'address'.random_int(1000, 2000);
        $addressCreatorException = new Exception('error'.random_int(1000, 2000));

        $this->addressCreator
            ->expects(static::once())
            ->method('fromString')
            ->with($address)
            ->willThrowException($addressCreatorException)
        ;

        $this->expectException(BitcoinAddressValidatorException::class);
        $this->validator->validate($address);
    }

    /**
     * @covers ::validate
     */
    public function testExpectTrueWhenAddressIsValid(): void
    {
        $address = 'address'.random_int(1000, 2000);

        $this->addressCreator
            ->expects(static::once())
            ->method('fromString')
            ->with($address)
        ;

        $this->validator->validate($address);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Factory;

use InvalidArgumentException;
use Jorijn\Bl3pDca\Client\Bl3PClientInterface;
use Jorijn\Bl3pDca\Factory\Bl3PClientFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Factory\Bl3PClientFactory
 */
class Bl3PClientFactoryTest extends TestCase
{
    /**
     * @covers ::createApi
     */
    public function testCreateApiWithoutConfigurationPresent(): void
    {
        $factory = new Bl3PClientFactory();

        $this->expectException(InvalidArgumentException::class);

        $factory->createApi();
    }

    /**
     * @covers ::createApi
     */
    public function testCreateApi(): void
    {
        $factory = new Bl3PClientFactory();

        $_SERVER['BL3P_API_URL'] = 'url'.mt_rand();
        $_SERVER['BL3P_PUBLIC_KEY'] = 'pub_key'.mt_rand();
        $_SERVER['BL3P_PRIVATE_KEY'] = 'private_key'.mt_rand();

        self::assertInstanceOf(Bl3PClientInterface::class, $factory->createApi());
    }
}

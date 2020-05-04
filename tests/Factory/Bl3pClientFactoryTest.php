<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Factory;

use Jorijn\Bl3pDca\Client\Bl3pClient;
use Jorijn\Bl3pDca\Factory\Bl3pClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Factory\Bl3pClientFactory
 *
 * @internal
 */
final class Bl3pClientFactoryTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::createApi
     */
    public function testApiIsCreatedWithCorrectProperties(): void
    {
        $url = 'url'.random_int(1000, 2000);
        $publicKey = 'pubkey'.random_int(1000, 2000);
        $privateKey = 'privatekey'.random_int(1000, 2000);

        $factory = new Bl3pClientFactory($url, $publicKey, $privateKey, $this->createMock(LoggerInterface::class));
        $client = $factory->createApi();

        $reflection = new ReflectionClass(Bl3pClient::class);
        $propertyUrl = $reflection->getProperty('url');
        $propertyPublicKey = $reflection->getProperty('publicKey');
        $propertyPrivateKey = $reflection->getProperty('privateKey');

        $propertyUrl->setAccessible(true);
        $propertyPublicKey->setAccessible(true);
        $propertyPrivateKey->setAccessible(true);

        static::assertSame($url, $propertyUrl->getValue($client));
        static::assertSame($publicKey, $propertyPublicKey->getValue($client));
        static::assertSame($privateKey, $propertyPrivateKey->getValue($client));
    }
}

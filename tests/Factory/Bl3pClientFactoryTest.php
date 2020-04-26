<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bl3pDca\Factory;

use Jorijn\Bl3pDca\Client\Bl3pClient;
use Jorijn\Bl3pDca\Factory\Bl3pClientFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @coversDefaultClass \Jorijn\Bl3pDca\Factory\Bl3pClientFactory
 */
class Bl3pClientFactoryTest extends TestCase
{
    /**
     * @covers ::createApi
     * @covers ::__construct
     */
    public function testApiIsCreatedWithCorrectProperties(): void
    {
        $url = 'url'.mt_rand();
        $publicKey = 'pubkey'.mt_rand();
        $privateKey = 'privatekey'.mt_rand();

        $factory = new Bl3pClientFactory($url, $publicKey, $privateKey);
        $client = $factory->createApi();

        $reflection = new ReflectionClass(Bl3pClient::class);
        $propertyUrl = $reflection->getProperty('url');
        $propertyPublicKey = $reflection->getProperty('publicKey');
        $propertyPrivateKey = $reflection->getProperty('privateKey');

        $propertyUrl->setAccessible(true);
        $propertyPublicKey->setAccessible(true);
        $propertyPrivateKey->setAccessible(true);

        self::assertSame($url, $propertyUrl->getValue($client));
        self::assertSame($publicKey, $propertyPublicKey->getValue($client));
        self::assertSame($privateKey, $propertyPrivateKey->getValue($client));
    }
}

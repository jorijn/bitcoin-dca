<?php

declare(strict_types=1);

namespace Tests\Jorijn\Bitcoin\Dca\Service\Kraken;

use Jorijn\Bitcoin\Dca\Client\KrakenClientInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\Service\Kraken\KrakenWithdrawService
 * @covers ::__construct
 *
 * @internal
 */
final class KrakenWithdrawServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $this->client = $this->createMock(KrakenClientInterface::class);
    }
}

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

namespace Tests\Jorijn\Bitcoin\Dca\EventListener\Notifications;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Jorijn\Bitcoin\Dca\EventListener\Notifications\SendTelegramOnBuyListener;
use Jorijn\Bitcoin\Dca\Model\CompletedBuyOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramTransport;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\Notifications\SendTelegramOnBuyListener
 *
 * @internal
 */
final class SendTelegramOnBuyListenerTest extends TestCase
{
    /** @var HttpClientInterface|mixed|MockObject */
    private $httpClient;
    /** @var EventDispatcherInterface|mixed|MockObject */
    private $eventDispatcher;
    private TelegramTransport $transport;
    private string $exchange;
    private SendTelegramOnBuyListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->transport = new TelegramTransport('', '', $this->httpClient);
        $this->exchange = 'e'.random_int(1000, 2000);
        $this->listener = new SendTelegramOnBuyListener(
            $this->transport,
            $this->eventDispatcher,
            $this->exchange,
            true
        );
    }

    /**
     * @covers ::onBuy
     */
    public function testListenerDoesNotActWhenItIsDisables(): void
    {
        $this->listener = new SendTelegramOnBuyListener(
            $this->transport,
            $this->eventDispatcher,
            $this->exchange,
            false
        );

        $completedBuyOrder = (new CompletedBuyOrder());
        $buySuccessEvent = new BuySuccessEvent($completedBuyOrder);

        $this->httpClient->expects(static::never())->method('request');

        $this->listener->onBuy($buySuccessEvent);
    }

    /**
     * @covers ::onBuy
     */
    public function testListenerSendsOutTelegramMessageOnBuyEvent(): void
    {
        $completedBuyOrder = (new CompletedBuyOrder())
            ->setAmountInSatoshis($amountInSatoshis = random_int(100000, 200000))
            ->setDisplayAmountBought($displayAmountBought = 'dab'.random_int(100000, 200000))
            ->setDisplayAmountSpent($displayAmountSpent = 'das'.random_int(100000, 200000))
            ->setDisplayFeesSpent($displayFeesSpent = 'dfs'.random_int(100000, 200000))
            ->setDisplayAveragePrice($displayAveragePrice = 'dap'.random_int(100000, 200000))
            ->setDisplayAmountSpentCurrency('dasc'.random_int(100000, 200000))
        ;

        $tag = 't'.random_int(1000, 2000);
        $buySuccessEvent = new BuySuccessEvent($completedBuyOrder, $tag);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn(['result' => ['message_id' => 1]]);

        $this->httpClient
            ->expects(static::once())
            ->method('request')
            ->with(
                'POST',
                static::anything(),
                static::callback(function (array $body) use (
                    $tag,
                    $amountInSatoshis,
                    $displayAmountBought,
                    $displayAmountSpent,
                    $displayFeesSpent,
                    $displayAveragePrice
                ): bool {
                    self::assertArrayHasKey('json', $body);
                    self::assertArrayHasKey('text', $body['json']);
                    self::assertArrayHasKey('disable_web_page_preview', $body['json']);
                    self::assertArrayHasKey('parse_mode', $body['json']);
                    self::assertSame('HTML', $body['json']['parse_mode']);
                    self::assertTrue($body['json']['disable_web_page_preview']);

                    self::assertStringContainsString(number_format($amountInSatoshis), $body['json']['text']);
                    self::assertStringContainsString($displayAmountBought, $body['json']['text']);
                    self::assertStringContainsString($displayAmountSpent, $body['json']['text']);
                    self::assertStringContainsString($displayFeesSpent, $body['json']['text']);
                    self::assertStringContainsString($displayAveragePrice, $body['json']['text']);
                    self::assertStringContainsString($tag, $body['json']['text']);

                    return true;
                })
            )
            ->willReturn($response)
        ;

        $this->listener->onBuy($buySuccessEvent);
    }
}

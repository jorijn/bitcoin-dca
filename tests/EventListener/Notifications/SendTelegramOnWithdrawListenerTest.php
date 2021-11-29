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

use Jorijn\Bitcoin\Dca\Event\WithdrawSuccessEvent;
use Jorijn\Bitcoin\Dca\EventListener\Notifications\SendTelegramOnWithdrawListener;
use Jorijn\Bitcoin\Dca\Model\CompletedWithdraw;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramTransport;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @coversDefaultClass \Jorijn\Bitcoin\Dca\EventListener\Notifications\SendTelegramOnWithdrawListener
 *
 * @internal
 */
final class SendTelegramOnWithdrawListenerTest extends TestCase
{
    /** @var HttpClientInterface|mixed|MockObject */
    private $httpClient;
    /** @var EventDispatcherInterface|mixed|MockObject */
    private $eventDispatcher;
    private TelegramTransport $transport;
    private string $exchange;
    private SendTelegramOnWithdrawListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->transport = new TelegramTransport('', '', $this->httpClient);
        $this->exchange = 'e'.random_int(2000, 3000);
        $this->listener = new SendTelegramOnWithdrawListener(
            $this->transport,
            $this->eventDispatcher,
            $this->exchange,
            true
        );
    }

    /**
     * @covers ::onWithdraw
     */
    public function testListenerDoesNotActWhenItIsDisables(): void
    {
        $this->listener = new SendTelegramOnWithdrawListener(
            $this->transport,
            $this->eventDispatcher,
            $this->exchange,
            false
        );

        $completedWithdraw = (new CompletedWithdraw('', 0, ''));
        $buyEvent = new WithdrawSuccessEvent($completedWithdraw);

        $this->httpClient->expects(static::never())->method('request');

        $this->listener->onWithdraw($buyEvent);
    }

    /**
     * @covers ::onWithdraw
     */
    public function testListenerSendsOutTelegramMessageOnBuyEvent(): void
    {
        $recipientAddress = 'ra'.random_int(1000, 2000);
        $withdrawID = 'wid'.random_int(1000, 2000);
        $amountInSatoshis = random_int(100000, 200000);
        $completedWithdraw = new CompletedWithdraw($recipientAddress, $amountInSatoshis, $withdrawID);
        $tag = 't'.random_int(1000, 2000);
        $withdrawEvent = new WithdrawSuccessEvent($completedWithdraw, $tag);

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
                    $withdrawID
                ) {
                    self::assertArrayHasKey('json', $body);
                    self::assertArrayHasKey('text', $body['json']);
                    self::assertArrayHasKey('disable_web_page_preview', $body['json']);
                    self::assertArrayHasKey('parse_mode', $body['json']);
                    self::assertSame('HTML', $body['json']['parse_mode']);
                    self::assertTrue($body['json']['disable_web_page_preview']);

                    self::assertStringContainsString(number_format($amountInSatoshis), $body['json']['text']);
                    self::assertStringContainsString($withdrawID, $body['json']['text']);
                    self::assertStringContainsString($tag, $body['json']['text']);

                    return true;
                })
            )
            ->willReturn($response)
        ;

        $this->listener->onWithdraw($withdrawEvent);
    }
}

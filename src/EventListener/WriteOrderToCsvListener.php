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

namespace Jorijn\Bitcoin\Dca\EventListener;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class WriteOrderToCsvListener
{
    use LoggerAwareTrait;
    use SerializerAwareTrait;

    public function __construct(
        SerializerInterface $serializer,
        LoggerInterface $logger,
        protected ?string $csvLocation
    ) {
        $this->setSerializer($serializer);
        $this->setLogger($logger);
    }

    public function onSuccessfulBuy(BuySuccessEvent $buySuccessEvent): void
    {
        if (null === $this->csvLocation) {
            return;
        }

        try {
            $addHeaders = !file_exists($this->csvLocation) || 0 === filesize($this->csvLocation);
            $csvData = $this->serializer->serialize(
                $buySuccessEvent->getBuyOrder(),
                'csv',
                [CsvEncoder::NO_HEADERS_KEY => !$addHeaders]
            );

            $resource = fopen($this->csvLocation, 'a');
            fwrite($resource, $csvData);
            fclose($resource);

            $this->logger->info(
                'wrote order information to file',
                [
                    'file' => $this->csvLocation,
                    'add_headers' => $addHeaders,
                ]
            );
        } catch (Throwable $exception) {
            $this->logger->error(
                'unable to write order to file',
                [
                    'reason' => $exception->getMessage(),
                    'exception' => $exception,
                ]
            );
        }
    }
}

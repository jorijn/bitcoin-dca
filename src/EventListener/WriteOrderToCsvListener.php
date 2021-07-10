<?php

declare(strict_types=1);

namespace Jorijn\Bitcoin\Dca\EventListener;

use Jorijn\Bitcoin\Dca\Event\BuySuccessEvent;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

class WriteOrderToCsvListener
{
    use LoggerAwareTrait;
    use SerializerAwareTrait;

    protected ?string $csvLocation;

    public function __construct(SerializerInterface $serializer, LoggerInterface $logger, ?string $csvLocation)
    {
        $this->setSerializer($serializer);
        $this->setLogger($logger);
        $this->csvLocation = $csvLocation;
    }

    public function onSuccessfulBuy(BuySuccessEvent $event): void
    {
        if (null === $this->csvLocation) {
            return;
        }

        try {
            $addHeaders = !file_exists($this->csvLocation) || 0 === filesize($this->csvLocation);
            $csvData = $this->serializer->serialize(
                $event->getBuyOrder(),
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
        } catch (\Throwable $exception) {
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

<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Transport;

use Madgeek\Msg91\Support\Msg91Response;
use Psr\Log\LoggerInterface;

final class LogTransport implements Transport
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function send(string $endpoint, array $payload): Msg91Response
    {
        $this->logger->info('MSG91 message logged.', [
            'endpoint' => $endpoint,
            'payload' => $payload,
        ]);

        return Msg91Response::logged();
    }
}

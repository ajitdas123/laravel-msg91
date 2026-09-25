<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Support;

use Illuminate\Contracts\Events\Dispatcher;
use Madgeek\Msg91\Events\MessageFailed;
use Madgeek\Msg91\Events\MessageSending;
use Madgeek\Msg91\Events\MessageSent;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Transport\Transport;

final class Delivery
{
    public function __construct(
        private readonly Transport $transport,
        private readonly Dispatcher $events,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $channel, string $endpoint, array $payload): Msg91Response
    {
        $this->events->dispatch(new MessageSending($channel, $endpoint, $payload));

        try {
            $response = $this->transport->send($endpoint, $payload);
        } catch (Msg91Exception $exception) {
            $this->events->dispatch(new MessageFailed($channel, $endpoint, $payload, $exception));

            throw $exception;
        }

        $this->events->dispatch(new MessageSent($channel, $endpoint, $payload, $response));

        return $response;
    }

    public function transport(): Transport
    {
        return $this->transport;
    }
}

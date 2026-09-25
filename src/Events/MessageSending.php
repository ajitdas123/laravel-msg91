<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Events;

final class MessageSending
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $channel,
        public string $endpoint,
        public array $payload,
    ) {}
}

<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Events;

use Madgeek\Msg91\Support\Msg91Response;

final class MessageSent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $channel,
        public string $endpoint,
        public array $payload,
        public Msg91Response $response,
    ) {}
}

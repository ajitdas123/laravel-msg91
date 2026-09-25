<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Events;

use Madgeek\Msg91\Exceptions\Msg91Exception;

final class MessageFailed
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $channel,
        public string $endpoint,
        public array $payload,
        public Msg91Exception $exception,
    ) {}
}

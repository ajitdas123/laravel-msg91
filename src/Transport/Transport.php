<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Transport;

use Madgeek\Msg91\Support\Msg91Response;

interface Transport
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $endpoint, array $payload): Msg91Response;
}

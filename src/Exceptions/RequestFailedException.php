<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Exceptions;

use Madgeek\Msg91\Support\Msg91Response;

final class RequestFailedException extends Msg91Exception
{
    public function __construct(string $message, public readonly Msg91Response $response)
    {
        parent::__construct($message);
    }
}

<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Exceptions;

use Madgeek\Msg91\Support\Msg91Response;

final class AuthenticationException extends Msg91Exception
{
    public function __construct(
        string $message = 'MSG91 authentication failed.',
        public readonly ?Msg91Response $response = null,
    ) {
        parent::__construct($message);
    }
}

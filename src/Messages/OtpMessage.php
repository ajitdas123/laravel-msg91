<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Messages;

final readonly class OtpMessage
{
    public function __construct(
        public string $action,
        public string $to,
        public ?string $template = null,
        public ?string $otp = null,
    ) {}
}

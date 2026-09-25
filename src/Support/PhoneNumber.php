<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Support;

use Madgeek\Msg91\Exceptions\InvalidRecipientException;

final readonly class PhoneNumber
{
    private function __construct(public string $number) {}

    public static function from(string $number, string $countryCode): self
    {
        $digits = preg_replace('/\D+/', '', $number);
        $prefix = preg_replace('/\D+/', '', $countryCode) ?? '';

        if (! is_string($digits) || $digits === '') {
            throw new InvalidRecipientException('The recipient phone number is empty or not numeric.');
        }

        $digits = ltrim($digits, '0');

        if ($digits === '') {
            throw new InvalidRecipientException('The recipient phone number is empty or not numeric.');
        }

        if ($prefix !== '' && ! str_starts_with($digits, $prefix) && strlen($digits) <= 10) {
            $digits = $prefix.$digits;
        }

        $length = strlen($digits);

        if ($length < 8 || $length > 15) {
            throw new InvalidRecipientException("The recipient phone number [{$digits}] is invalid.");
        }

        return new self($digits);
    }
}

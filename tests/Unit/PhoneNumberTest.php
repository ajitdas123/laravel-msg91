<?php

declare(strict_types=1);

use Madgeek\Msg91\Exceptions\InvalidRecipientException;
use Madgeek\Msg91\Support\PhoneNumber;

it('prefixes a local number with the country code', function () {
    expect(PhoneNumber::from('9876543210', '91')->number)->toBe('919876543210')
        ->and(PhoneNumber::from('919876543210', '91')->number)->toBe('919876543210')
        ->and(PhoneNumber::from('+91 98765-43210', '91')->number)->toBe('919876543210')
        ->and(PhoneNumber::from('09876543210', '91')->number)->toBe('919876543210')
        ->and(PhoneNumber::from('2025550123', '1')->number)->toBe('12025550123')
        ->and(PhoneNumber::from('14155552671', '91')->number)->toBe('14155552671');
});

it('rejects empty and invalid phone numbers', function () {
    expect(fn () => PhoneNumber::from('abc', '91'))->toThrow(InvalidRecipientException::class)
        ->and(fn () => PhoneNumber::from('000', '91'))->toThrow(InvalidRecipientException::class)
        ->and(fn () => PhoneNumber::from('12345', '91'))->toThrow(InvalidRecipientException::class)
        ->and(fn () => PhoneNumber::from('1234567890123456', '91'))->toThrow(InvalidRecipientException::class);
});

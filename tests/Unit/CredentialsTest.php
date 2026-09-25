<?php

declare(strict_types=1);

use Madgeek\Msg91\Credentials\Credentials;
use Madgeek\Msg91\Exceptions\Msg91Exception;

it('builds credentials from config arrays', function () {
    $sparse = Credentials::fromConfig([
        'auth_key' => '',
        'country_code' => null,
        'sender_id' => '',
        'whatsapp' => 'nope',
        'otp' => [
            'template_id' => 'otp',
            'expiry' => '9',
            'length' => null,
        ],
        'endpoints' => [
            'sms' => 'https://example.test/sms',
            'blank' => '',
            1 => 'https://example.test/numeric',
            'skipped' => 5,
        ],
    ]);

    $explicit = Credentials::fromConfig([
        'auth_key' => 'key',
        'country_code' => '1',
        'sender_id' => 'SENDER',
        'whatsapp' => [
            'integrated_number' => '14155552671',
            'namespace' => 'space',
            'language' => 'en_US',
        ],
        'otp' => [
            'template_id' => '',
            'expiry' => 4,
            'length' => 8,
        ],
        'endpoints' => 'nope',
    ]);

    expect($sparse->authKey)->toBe('')
        ->and($sparse->countryCode)->toBe('91')
        ->and($sparse->senderId)->toBeNull()
        ->and($sparse->whatsappIntegratedNumber)->toBeNull()
        ->and($sparse->whatsappLanguage)->toBe('en')
        ->and($sparse->otpTemplateId)->toBe('otp')
        ->and($sparse->otpExpiry)->toBe(9)
        ->and($sparse->otpLength)->toBe(6)
        ->and($sparse->endpoint('sms'))->toBe('https://example.test/sms')
        ->and(fn () => $sparse->endpoint('whatsapp'))->toThrow(Msg91Exception::class, 'MSG91 endpoint [whatsapp] is not configured.')
        ->and($explicit->authKey)->toBe('key')
        ->and($explicit->countryCode)->toBe('1')
        ->and($explicit->senderId)->toBe('SENDER')
        ->and($explicit->whatsappNamespace)->toBe('space')
        ->and($explicit->otpTemplateId)->toBeNull()
        ->and($explicit->otpExpiry)->toBe(4)
        ->and($explicit->otpLength)->toBe(8)
        ->and($explicit->endpoints)->toBe([]);
});

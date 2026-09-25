<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Msg91ServiceProvider;
use Madgeek\Msg91\Transport\HttpTransport;
use Madgeek\Msg91\Transport\LogTransport;
use Madgeek\Msg91\Transport\Transport;

it('merges package config and lets the app override it', function () {
    expect(config('msg91.driver'))->toBe('log')
        ->and(config('msg91.endpoints.sms'))->toBe('https://control.msg91.com/api/v5/flow/')
        ->and(config('msg91.endpoints.whatsapp'))->toBe('https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/')
        ->and(config('msg91.endpoints.otp'))->toBe('https://control.msg91.com/api/v5/otp')
        ->and(config('msg91.endpoints.otp_verify'))->toBe('https://control.msg91.com/api/v5/otp/verify')
        ->and(config('msg91.endpoints.otp_retry'))->toBe('https://control.msg91.com/api/v5/otp/retry');

    config(['msg91.country_code' => '1']);

    expect(config('msg91.country_code'))->toBe('1');
});

it('publishes the msg91 config file', function () {
    expect(ServiceProvider::pathsToPublish(Msg91ServiceProvider::class, 'laravel-msg91-config'))
        ->toContain(config_path('msg91.php'))
        ->and(ServiceProvider::pathsToPublish(Msg91ServiceProvider::class, 'laravel-msg91'))
        ->toContain(config_path('msg91.php'));
});

it('binds the log and http transports from the driver', function () {
    expect(app(Transport::class))->toBeInstanceOf(LogTransport::class);

    config(['msg91.driver' => 'http']);
    resetMsg91();

    expect(app(Transport::class))->toBeInstanceOf(HttpTransport::class);
});

it('rejects an unknown driver', function () {
    config(['msg91.driver' => 'pusher']);
    resetMsg91();

    expect(fn () => app(Transport::class))->toThrow(Msg91Exception::class, 'Unsupported MSG91 driver [pusher].');
});

it('rejects a driver that is not a string', function () {
    config(['msg91.driver' => ['http']]);
    resetMsg91();

    expect(fn () => app(Transport::class))->toThrow(Msg91Exception::class, 'MSG91 driver must be a string.');
});

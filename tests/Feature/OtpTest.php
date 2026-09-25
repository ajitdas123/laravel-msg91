<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Facades\Msg91;

it('sends verifies and resends an otp', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response(['type' => 'success', 'message' => 'OK'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::otp()->to('9876543210')->template('otp-template')->send();
    Msg91::otp()->verify('9876543210', '123456');
    Msg91::otp()->resend('9876543210');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://control.msg91.com/api/v5/otp'
        && $request['mobile'] === '919876543210'
        && $request['template_id'] === 'otp-template'
        && $request['otp_expiry'] === 5
        && $request['otp_length'] === 6);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://control.msg91.com/api/v5/otp/verify'
        && $request['mobile'] === '919876543210'
        && $request['otp'] === '123456');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://control.msg91.com/api/v5/otp/retry'
        && $request['mobile'] === '919876543210'
        && $request['retrytype'] === 'text');
});

it('uses the configured otp template', function () {
    $response = Msg91::otp()->to('9876543210')->send();

    expect($response->message)->toBe('logged');
});

it('requires an otp recipient and template', function () {
    expect(fn () => Msg91::otp()->template('otp-template')->send())
        ->toThrow(Msg91Exception::class, 'OTP recipient is required.');

    config(['msg91.otp.template_id' => '']);
    resetMsg91();

    expect(fn () => Msg91::otp()->to('9876543210')->send())
        ->toThrow(Msg91Exception::class, 'OTP template id is required.');
});

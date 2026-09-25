<?php

declare(strict_types=1);

use Madgeek\Msg91\Support\Msg91Response;

it('normalizes http payloads', function () {
    $success = Msg91Response::fromHttp(200, [
        'type' => 'success',
        'message' => 'OK',
        'request_id' => 'req-1',
    ]);

    $numeric = Msg91Response::fromHttp(200, [
        'requestId' => 44,
    ]);

    $failed = Msg91Response::fromHttp(200, [
        'type' => 'error',
        'message' => 'Template missing',
    ]);

    expect($success->successful)->toBeTrue()
        ->and($success->requestId)->toBe('req-1')
        ->and($success->message)->toBe('OK')
        ->and($numeric->requestId)->toBe('44')
        ->and($failed->successful)->toBeFalse()
        ->and(Msg91Response::logged()->message)->toBe('logged')
        ->and(Msg91Response::faked()->message)->toBe('faked');
});

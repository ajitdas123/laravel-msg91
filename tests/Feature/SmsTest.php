<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Madgeek\Msg91\Events\MessageFailed;
use Madgeek\Msg91\Events\MessageSending;
use Madgeek\Msg91\Events\MessageSent;
use Madgeek\Msg91\Exceptions\AuthenticationException;
use Madgeek\Msg91\Exceptions\InvalidRecipientException;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Exceptions\RequestFailedException;
use Madgeek\Msg91\Facades\Msg91;

it('logs an sms payload without calling msg91', function () {
    $logs = captureLogs();

    $response = Msg91::sms()
        ->to('9876543210')
        ->template('template-1')
        ->variable('VAR1', 'Ajit')
        ->send();

    expect($response->successful)->toBeTrue()
        ->and($response->message)->toBe('logged')
        ->and($logs)->toHaveCount(1)
        ->and($logs[0]->context['payload']['template_id'])->toBe('template-1')
        ->and($logs[0]->context['payload']['recipients'][0]['mobiles'])->toBe('919876543210')
        ->and($logs[0]->context['payload']['recipients'][0]['VAR1'])->toBe('Ajit');
});

it('dispatches sending and sent events', function () {
    Event::fake([MessageSending::class, MessageSent::class, MessageFailed::class]);
    resetMsg91();

    Msg91::sms()->to('9876543210')->template('flow-1')->send();

    Event::assertDispatched(MessageSending::class, fn (MessageSending $event): bool => $event->channel === 'sms');
    Event::assertDispatched(MessageSent::class, fn (MessageSent $event): bool => $event->channel === 'sms' && $event->response->message === 'logged');
    Event::assertNotDispatched(MessageFailed::class);
});

it('does not dispatch events for an invalid recipient', function () {
    Event::fake();
    resetMsg91();

    expect(fn () => Msg91::sms()->to('abc')->template('flow-1')->send())->toThrow(InvalidRecipientException::class);

    Event::assertNotDispatched(MessageSending::class);
});

it('requires an sms template id', function () {
    expect(fn () => Msg91::sms()->to('9876543210')->send())->toThrow(Msg91Exception::class, 'SMS template id is required.');
});

it('posts the sms flow payload', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response([
            'type' => 'success',
            'message' => 'OK',
            'request_id' => 'req-1',
        ], 200),
    ]);

    config(['msg91.driver' => 'http', 'msg91.http.retry' => 1]);
    resetMsg91();

    $response = Msg91::sms()
        ->to('9876543210')
        ->template('template-1')
        ->shortUrl()
        ->shortUrlExpiry(3600)
        ->realTimeResponse()
        ->variable('VAR1', 'Ajit')
        ->send();

    expect($response->requestId)->toBe('req-1');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://control.msg91.com/api/v5/flow/'
        && $request->hasHeader('authkey', 'test-auth-key')
        && $request['template_id'] === 'template-1'
        && $request['short_url'] === '1'
        && $request['short_url_expiry'] === '3600'
        && $request['realTimeResponse'] === '1'
        && $request['recipients'][0]['mobiles'] === '919876543210'
        && $request['recipients'][0]['VAR1'] === 'Ajit'
        && ! array_key_exists('flow_id', $request->data())
        && ! array_key_exists('sender', $request->data()));
});

it('can turn sms short urls off', function () {
    Http::fake([
        '*' => Http::response(['type' => 'success'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::sms()->to('9876543210')->template('template-1')->shortUrl(false)->send();

    Http::assertSent(fn (Request $request): bool => $request['short_url'] === '0'
        && ! array_key_exists('short_url_expiry', $request->data())
        && ! array_key_exists('realTimeResponse', $request->data()));
});

it('throws when the auth key is missing', function () {
    Http::fake();
    config(['msg91.driver' => 'http', 'msg91.auth_key' => '']);
    resetMsg91();

    expect(fn () => Msg91::sms()->to('9876543210')->template('flow-1')->send())->toThrow(AuthenticationException::class, 'MSG91 auth key is not configured.');

    Http::assertNothingSent();
});

it('throws when msg91 rejects the auth key', function () {
    Event::fake();
    Http::fake([
        '*' => Http::response(['type' => 'error', 'message' => 'Authentication failure'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    expect(fn () => Msg91::sms()->to('9876543210')->template('flow-1')->send())
        ->toThrow(AuthenticationException::class, 'Authentication failure');

    Event::assertDispatched(MessageFailed::class);
    Event::assertNotDispatched(MessageSent::class);
});

it('throws when msg91 returns an unauthorized status', function () {
    Http::fake([
        '*' => Http::response(['message' => 'nope'], 401),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    expect(fn () => Msg91::sms()->to('9876543210')->template('flow-1')->send())->toThrow(AuthenticationException::class);
});

it('throws when the sms request fails', function () {
    Http::fake([
        '*' => Http::response(['type' => 'error', 'message' => 'Template missing'], 422),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    expect(fn () => Msg91::sms()->to('9876543210')->template('flow-1')->send())
        ->toThrow(RequestFailedException::class, 'Template missing');
});

it('wraps connection failures', function () {
    Http::fake(function (): never {
        throw new ConnectionException('Could not connect.');
    });
    config(['msg91.driver' => 'http']);
    resetMsg91();

    expect(fn () => Msg91::sms()->to('9876543210')->template('flow-1')->send())
        ->toThrow(RequestFailedException::class, 'Could not connect.');
});

it('requires the sms endpoint to be configured', function () {
    config(['msg91.endpoints.sms' => '']);
    resetMsg91();

    expect(fn () => Msg91::sms()->to('9876543210')->template('flow-1')->send())
        ->toThrow(Msg91Exception::class, 'MSG91 endpoint [sms] is not configured.');
});

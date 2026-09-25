<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Facades\Msg91;

it('posts a whatsapp template payload', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response(['type' => 'success', 'message' => 'OK'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::whatsapp()
        ->to('9876543210')
        ->from('919811111111')
        ->template('order_shipped')
        ->language('hi')
        ->namespace('custom-namespace')
        ->header('https://cdn.example/a.png', 'image')
        ->body('Ajit', 'ORD-1')
        ->button('track')
        ->send();

    Http::assertSent(function (Request $request): bool {
        $template = $request['payload']['template'];

        return $request->url() === 'https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/'
            && $request->hasHeader('authkey', 'test-auth-key')
            && $request['integrated_number'] === '919811111111'
            && $request['content_type'] === 'template'
            && $template['name'] === 'order_shipped'
            && $template['language']['code'] === 'hi'
            && $template['namespace'] === 'custom-namespace'
            && $template['to_and_components'][0]['to'] === ['919876543210']
            && $template['to_and_components'][0]['components']['body_1']['value'] === 'Ajit'
            && $template['to_and_components'][0]['components']['body_2']['value'] === 'ORD-1'
            && $template['to_and_components'][0]['components']['header_1']['type'] === 'image'
            && $template['to_and_components'][0]['components']['button_1']['subtype'] === 'url'
            && $template['to_and_components'][0]['components']['button_1']['value'] === 'track';
    });
});

it('sends an empty component object when the template has no variables', function () {
    Http::fake([
        '*' => Http::response(['type' => 'success'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::whatsapp()->to('9876543210')->template('hello')->send();

    Http::assertSent(fn (Request $request): bool => str_contains($request->body(), '"components":{}')
        && $request['payload']['template']['language']['code'] === 'en'
        && $request['payload']['template']['namespace'] === 'test-namespace'
        && $request['integrated_number'] === '919800000000');
});

it('requires a whatsapp template, integrated number, and namespace', function () {
    expect(fn () => Msg91::whatsapp()->to('9876543210')->send())
        ->toThrow(Msg91Exception::class, 'WhatsApp template name is required.');

    config(['msg91.whatsapp.integrated_number' => '']);
    resetMsg91();

    expect(fn () => Msg91::whatsapp()->to('9876543210')->template('hello')->send())
        ->toThrow(Msg91Exception::class, 'WhatsApp integrated number is required.');

    config([
        'msg91.whatsapp.integrated_number' => '919800000000',
        'msg91.whatsapp.namespace' => '',
    ]);
    resetMsg91();

    expect(fn () => Msg91::whatsapp()->to('9876543210')->template('hello')->send())
        ->toThrow(Msg91Exception::class, 'WhatsApp template namespace is required.');
});

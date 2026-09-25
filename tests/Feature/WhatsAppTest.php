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

it('posts a single whatsapp text message', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response(['type' => 'success'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::whatsapp()->to('9876543210')->text('Hello from MSG91')->send();

    Http::assertSent(function (Request $request): bool {
        return str_starts_with($request->url(), 'https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/?')
            && $request['integrated_number'] === '919800000000'
            && $request['recipient_number'] === '919876543210'
            && $request['content_type'] === 'text'
            && $request['text'] === 'Hello from MSG91'
            && str_contains($request->url(), 'text=Hello%20from%20MSG91');
    });
});

it('posts an interactive whatsapp button message', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response(['type' => 'success'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::whatsapp()
        ->to('9876543210')
        ->buttons('Choose one')
        ->headingImage('https://cdn.example/a.jpg')
        ->footnote('Thanks')
        ->reply('yes', 'Yes')
        ->reply('no', 'No')
        ->send();

    Http::assertSent(function (Request $request): bool {
        $interactive = $request['interactive'];

        return $request->url() === 'https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/'
            && $request['content_type'] === 'interactive'
            && $request['recipient_number'] === '919876543210'
            && $interactive['type'] === 'button'
            && $interactive['header']['image']['link'] === 'https://cdn.example/a.jpg'
            && $interactive['body']['text'] === 'Choose one'
            && $interactive['footer']['text'] === 'Thanks'
            && $interactive['action']['buttons'][1]['reply'] === ['id' => 'no', 'title' => 'No'];
    });
});

it('posts an interactive whatsapp list message', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response(['type' => 'success'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::whatsapp()
        ->to('9876543210')
        ->listMessage('Pick a slot', 'Options')
        ->heading('Book')
        ->section('Morning', [
            ['id' => '9', 'title' => '9 AM', 'description' => 'First slot'],
        ])
        ->send();

    Http::assertSent(function (Request $request): bool {
        $section = $request['interactive']['action']['sections'][0];

        return $request['interactive']['type'] === 'list'
            && $request['interactive']['header']['text'] === 'Book'
            && $request['interactive']['action']['button'] === 'Options'
            && $section['rows'][0]['description'] === 'First slot';
    });
});

it('posts an interactive whatsapp location request', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response(['type' => 'success'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::whatsapp()->to('9876543210')->requestLocation('Please share your location')->send();

    Http::assertSent(fn (Request $request): bool => $request['interactive'] === [
        'type' => 'location_request_message',
        'body' => ['text' => 'Please share your location'],
        'action' => ['name' => 'send_location'],
    ]);
});

it('posts a single whatsapp product catalog message', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response(['type' => 'success'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::whatsapp()
        ->to('9876543210')
        ->product('763573428510437', '64tsm0iltw', 'Product with footer')
        ->footnote('product footer')
        ->send();

    Http::assertSent(fn (Request $request): bool => $request['interactive']['type'] === 'product'
        && $request['interactive']['action']['catalog_id'] === '763573428510437'
        && $request['interactive']['action']['product_retailer_id'] === '64tsm0iltw'
        && $request['interactive']['footer']['text'] === 'product footer');
});

it('posts a multiple product whatsapp catalog message', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response(['type' => 'success'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::whatsapp()
        ->to('9876543210')
        ->productList('763573428510437', 'Browse the catalog')
        ->heading('Shop')
        ->products('Shoes', 'sku-1', 'sku-2')
        ->send();

    Http::assertSent(fn (Request $request): bool => $request['interactive']['type'] === 'product_list'
        && $request['interactive']['header']['text'] === 'Shop'
        && $request['interactive']['action']['catalog_id'] === '763573428510437'
        && $request['interactive']['action']['sections'][0]['product_items'][1]['product_retailer_id'] === 'sku-2');
});

it('posts a whatsapp payment link', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response(['type' => 'success'], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    Msg91::whatsapp()
        ->to('9876543210')
        ->payment('Complete the payment below.')
        ->headingImage('https://cdn.example/product.jpg')
        ->footnote('Thank you')
        ->item('Shirt', 499, 1)
        ->send();

    Http::assertSent(fn (Request $request): bool => $request['interactive']['type'] === 'payment_link'
        && $request['interactive']['header']['image']['link'] === 'https://cdn.example/product.jpg'
        && $request['interactive']['items'][0] === [
            'name' => 'Shirt',
            'amount' => '499',
            'quantity' => '1',
        ]);
});

it('checks the whatsapp prepaid balance', function () {
    Http::fake([
        'https://control.msg91.com/*' => Http::response([
            'status' => 'success',
            'prepaid_balance' => 250.75,
        ], 200),
    ]);
    config(['msg91.driver' => 'http']);
    resetMsg91();

    $response = Msg91::whatsapp()->from('919811111111')->balance();

    expect($response->successful)->toBeTrue()
        ->and($response->body['prepaid_balance'])->toBe(250.75);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://control.msg91.com/api/v5/subscriptions/fetchPrepaidBalance'
        && $request['integrated_number'] === '919811111111'
        && $request['service'] === 'whatsapp');
});

it('rejects incomplete session whatsapp messages', function () {
    expect(fn () => Msg91::whatsapp()->to('9876543210')->text('')->send())
        ->toThrow(Msg91Exception::class, 'WhatsApp text is required.')
        ->and(fn () => Msg91::whatsapp()->to('9876543210')->buttons('Choose')->send())
        ->toThrow(Msg91Exception::class, 'at least one reply')
        ->and(fn () => Msg91::whatsapp()->to('9876543210')->buttons('Choose')->reply('1', 'A')->reply('2', 'B')->reply('3', 'C')->reply('4', 'D')->send())
        ->toThrow(Msg91Exception::class, 'at most 3 replies')
        ->and(fn () => Msg91::whatsapp()->to('9876543210')->productList('catalog', 'Browse')->send())
        ->toThrow(Msg91Exception::class, 'product list header is required.')
        ->and(fn () => Msg91::whatsapp()->to('9876543210')->payment('Pay')->send())
        ->toThrow(Msg91Exception::class, 'at least one item');
});

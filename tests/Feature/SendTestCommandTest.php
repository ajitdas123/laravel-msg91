<?php

declare(strict_types=1);

it('sends a test sms through the log driver', function () {
    $logs = captureLogs();

    $this->artisan('msg91:test', [
        'channel' => 'sms',
        'to' => '9876543210',
    ])->expectsOutputToContain('MSG91 sms test accepted.')->assertSuccessful();

    expect($logs[0]->context['payload']['template_id'])->toBe('test');
});

it('sends a test sms with an explicit template', function () {
    $logs = captureLogs();

    $this->artisan('msg91:test', [
        'channel' => 'sms',
        'to' => '9876543210',
        '--template' => 'template-1',
    ])->assertSuccessful();

    expect($logs[0]->context['payload']['template_id'])->toBe('template-1');
});

it('sends a test whatsapp message', function () {
    $logs = captureLogs();

    $this->artisan('msg91:test', [
        'channel' => 'whatsapp',
        'to' => '9876543210',
        '--template' => 'order_shipped',
    ])->expectsOutputToContain('MSG91 whatsapp test accepted.')->assertSuccessful();

    expect($logs[0]->context['payload']['payload']['template']['name'])->toBe('order_shipped');
});

it('sends a test whatsapp message with the default template', function () {
    $logs = captureLogs();

    $this->artisan('msg91:test', [
        'channel' => 'whatsapp',
        'to' => '9876543210',
    ])->assertSuccessful();

    expect($logs[0]->context['payload']['payload']['template']['name'])->toBe('test');
});

it('sends a test otp with the configured template', function () {
    $logs = captureLogs();

    $this->artisan('msg91:test', [
        'channel' => 'otp',
        'to' => '9876543210',
    ])->expectsOutputToContain('MSG91 otp test accepted.')->assertSuccessful();

    expect($logs[0]->context['payload']['template_id'])->toBe('otp-template');
});

it('sends a test otp with an explicit template', function () {
    $logs = captureLogs();

    $this->artisan('msg91:test', [
        'channel' => 'otp',
        'to' => '9876543210',
        '--template' => 'custom-otp',
    ])->assertSuccessful();

    expect($logs[0]->context['payload']['template_id'])->toBe('custom-otp');
});

it('rejects an unknown test channel', function () {
    $this->artisan('msg91:test', [
        'channel' => 'email',
        'to' => '9876543210',
    ])->expectsOutputToContain('Channel must be sms, whatsapp, or otp.')->assertFailed();
});

it('prints recipient errors from the test command', function () {
    $this->artisan('msg91:test', [
        'channel' => 'sms',
        'to' => 'abc',
    ])->expectsOutputToContain('empty or not numeric')->assertFailed();
});

<?php

declare(strict_types=1);

use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Facades\Msg91;
use Madgeek\Msg91\Messages\OtpMessage;
use Madgeek\Msg91\Messages\SmsMessage;
use Madgeek\Msg91\Messages\WhatsAppMessage;
use PHPUnit\Framework\AssertionFailedError;

it('records sms whatsapp and otp messages', function () {
    Msg91::fake();

    Msg91::sms()->to('9876543210')->template('flow-1')->variable('VAR1', 'Ajit')->send();
    Msg91::whatsapp()->to('9876543210')->template('order_shipped')->body('Ajit')->send();
    Msg91::otp()->to('9876543210')->send();
    Msg91::otp()->verify('9876543210', '123456');
    Msg91::otp()->resend('9876543210', 'voice');

    Msg91::assertSmsSent(fn (SmsMessage $message): bool => $message->to === '919876543210' && $message->variables['VAR1'] === 'Ajit');
    Msg91::assertWhatsAppSent(fn (WhatsAppMessage $message): bool => $message->template === 'order_shipped' && $message->components['body_1']['value'] === 'Ajit');
    Msg91::assertOtpSent(fn (OtpMessage $message): bool => $message->action === 'verify' && $message->to === '919876543210' && $message->template === null && $message->otp === '123456');
    Msg91::assertOtpSent(fn (OtpMessage $message): bool => $message->action === 'resend' && $message->to === '919876543210' && $message->otp === null);
    Msg91::assertOtpSent();
});

it('fails assertions when no matching message was recorded', function () {
    Msg91::fake();

    Msg91::sms()->to('9876543210')->template('flow-1')->send();

    expect(fn () => Msg91::assertSmsSent(fn (SmsMessage $message): bool => $message->templateId === 'other'))
        ->toThrow(AssertionFailedError::class)
        ->and(fn () => Msg91::assertWhatsAppSent())->toThrow(AssertionFailedError::class)
        ->and(fn () => Msg91::assertOtpSent(fn (OtpMessage $message): bool => $message->action === 'missing'))->toThrow(AssertionFailedError::class);
});

it('requires fake before assertions', function () {
    expect(fn () => Msg91::assertSmsSent())->toThrow(Msg91Exception::class, 'Msg91::fake() was not called.')
        ->and(fn () => Msg91::assertWhatsAppSent())->toThrow(Msg91Exception::class)
        ->and(fn () => Msg91::assertOtpSent())->toThrow(Msg91Exception::class);
});

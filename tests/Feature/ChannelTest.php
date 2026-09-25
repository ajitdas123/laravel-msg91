<?php

declare(strict_types=1);

use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Madgeek\Msg91\Channels\SmsChannel;
use Madgeek\Msg91\Channels\WhatsAppChannel;
use Madgeek\Msg91\Exceptions\InvalidRecipientException;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Facades\Msg91;
use Madgeek\Msg91\Messages\SmsMessage;
use Madgeek\Msg91\Messages\WhatsAppMessage;

it('sends an sms notification through the short channel name', function () {
    Msg91::fake();

    smsNotifiable('9876543210')->notify(smsNotification());

    Msg91::assertSmsSent(fn (SmsMessage $message): bool => $message->to === '919876543210' && $message->templateId === 'template-9');
});

it('sends an sms notification through the channel class', function () {
    Msg91::fake();

    smsNotifiable('9876543210')->notify(smsNotification(SmsChannel::class));

    Msg91::assertSmsSent();
});

it('prefers the phone number on the sms message', function () {
    Msg91::fake();

    smsNotifiable(null)->notify(new class extends Notification
    {
        public function via(mixed $notifiable): array
        {
            return ['msg91-sms'];
        }

        public function toMsg91Sms(object $notifiable): SmsMessage
        {
            return Msg91::sms()->to('8123456780')->template('template-9');
        }
    });

    Msg91::assertSmsSent(fn (SmsMessage $message): bool => $message->to === '918123456780');
});

it('rejects an sms notification without a recipient', function () {
    Msg91::fake();

    expect(fn () => smsNotifiable(null)->notify(smsNotification()))
        ->toThrow(InvalidRecipientException::class, 'No SMS recipient was provided.');
});

it('rejects an sms notification that is missing toMsg91Sms', function () {
    Msg91::fake();

    expect(fn () => smsNotifiable('9876543210')->notify(new class extends Notification
    {
        public function via(mixed $notifiable): array
        {
            return ['msg91-sms'];
        }
    }))->toThrow(Msg91Exception::class, 'must define toMsg91Sms()');
});

it('rejects an sms notification that returns the wrong message', function () {
    Msg91::fake();

    expect(fn () => smsNotifiable('9876543210')->notify(new class extends Notification
    {
        public function via(mixed $notifiable): array
        {
            return ['msg91-sms'];
        }

        public function toMsg91Sms(object $notifiable): string
        {
            return 'nope';
        }
    }))->toThrow(Msg91Exception::class, 'must return');
});

it('rejects an sms notifiable that cannot be routed', function () {
    Msg91::fake();

    $notification = smsNotification();

    expect(fn () => app(SmsChannel::class)->send(new stdClass, $notification))
        ->toThrow(InvalidRecipientException::class);
});

it('sends a whatsapp notification through the short channel name', function () {
    Msg91::fake();

    whatsappNotifiable('9876543210')->notify(whatsappNotification());

    Msg91::assertWhatsAppSent(fn (WhatsAppMessage $message): bool => $message->to === '919876543210' && $message->template === 'order_shipped');
});

it('sends a whatsapp notification through the channel class', function () {
    Msg91::fake();

    whatsappNotifiable('9876543210')->notify(whatsappNotification(WhatsAppChannel::class));

    Msg91::assertWhatsAppSent();
});

it('rejects a whatsapp notification without a recipient', function () {
    Msg91::fake();

    expect(fn () => whatsappNotifiable(null)->notify(whatsappNotification()))
        ->toThrow(InvalidRecipientException::class, 'No WhatsApp recipient was provided.');
});

it('rejects a whatsapp notification that is missing toMsg91Whatsapp', function () {
    Msg91::fake();

    expect(fn () => whatsappNotifiable('9876543210')->notify(new class extends Notification
    {
        public function via(mixed $notifiable): array
        {
            return ['msg91-whatsapp'];
        }
    }))->toThrow(Msg91Exception::class, 'must define toMsg91Whatsapp()');
});

it('rejects a whatsapp notification that returns the wrong message', function () {
    Msg91::fake();

    expect(fn () => whatsappNotifiable('9876543210')->notify(new class extends Notification
    {
        public function via(mixed $notifiable): array
        {
            return ['msg91-whatsapp'];
        }

        public function toMsg91Whatsapp(object $notifiable): string
        {
            return 'nope';
        }
    }))->toThrow(Msg91Exception::class, 'must return');
});

it('rejects a whatsapp notifiable that cannot be routed', function () {
    Msg91::fake();

    expect(fn () => app(WhatsAppChannel::class)->send(new stdClass, whatsappNotification()))
        ->toThrow(InvalidRecipientException::class);
});

function smsNotifiable(?string $phone): object
{
    return new class($phone)
    {
        use Notifiable;

        public function __construct(private readonly ?string $phone) {}

        public function routeNotificationForMsg91Sms(object $notification): ?string
        {
            return $this->phone;
        }
    };
}

function smsNotification(string $channel = 'msg91-sms'): Notification
{
    return new class($channel) extends Notification
    {
        public function __construct(private readonly string $channel) {}

        public function via(mixed $notifiable): array
        {
            return [$this->channel];
        }

        public function toMsg91Sms(object $notifiable): SmsMessage
        {
            return Msg91::sms()->template('template-9')->variable('VAR1', 'Ada');
        }
    };
}

function whatsappNotifiable(?string $phone): object
{
    return new class($phone)
    {
        use Notifiable;

        public function __construct(private readonly ?string $phone) {}

        public function routeNotificationForMsg91Whatsapp(object $notification): ?string
        {
            return $this->phone;
        }
    };
}

function whatsappNotification(string $channel = 'msg91-whatsapp'): Notification
{
    return new class($channel) extends Notification
    {
        public function __construct(private readonly string $channel) {}

        public function via(mixed $notifiable): array
        {
            return [$this->channel];
        }

        public function toMsg91Whatsapp(object $notifiable): WhatsAppMessage
        {
            return Msg91::whatsapp()->template('order_shipped')->body('Ada');
        }
    };
}

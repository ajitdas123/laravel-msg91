<div align="center">
    <h1>Laravel Msg91</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/ajit-das/laravel-msg91"><img src="https://img.shields.io/packagist/v/ajit-das/laravel-msg91.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/ajit-das/laravel-msg91"><img src="https://img.shields.io/packagist/php-v/ajit-das/laravel-msg91.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/ajit-das/laravel-msg91"><img src="https://badge.laravel.cloud/badge/ajit-das/laravel-msg91?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/ajit-das/laravel-msg91/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/ajit-das/laravel-msg91/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/ajit-das/laravel-msg91"><img src="https://img.shields.io/packagist/dt/ajit-das/laravel-msg91.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Laravel notification channels for MSG91 SMS, WhatsApp and OTP, with fluent message builders.

## Installation

You can install the package via Composer:

```bash
composer require ajit-das/laravel-msg91
```

Publish the config file:

```bash
php artisan vendor:publish --tag="laravel-msg91-config"
```

Set `MSG91_AUTH_KEY`. For WhatsApp, set the integrated number and template namespace. For OTP, set the template id. `MSG91_DRIVER=log` writes payloads to the log instead of calling MSG91, which is the right setting for local and staging.

## Usage

Send an SMS through the Flow API:

```php
use Madgeek\Msg91\Facades\Msg91;

Msg91::sms()
    ->to('9876543210')
    ->template('sms_template_id')
    ->shortUrl()
    ->variable('VAR1', 'Ajit')
    ->send();
```

Send a WhatsApp template:

```php
Msg91::whatsapp()
    ->to('9876543210')
    ->template('order_shipped')
    ->language('en')
    ->namespace('template_namespace')
    ->body('Ajit', 'ORD-1')
    ->send();
```

Send, verify, or resend an OTP:

```php
Msg91::otp()->to('9876543210')->template('otp_template')->send();
Msg91::otp()->verify('9876543210', '123456');
Msg91::otp()->resend('9876543210');
```

Local numbers are prefixed with `country_code` from `config/msg91.php` (default `91`).

### Notifications

Use `msg91-sms` and `msg91-whatsapp` from a notification. Return the phone number from `routeNotificationForMsg91Sms()` or `routeNotificationForMsg91Whatsapp()` when the message does not set `to()` itself.

```php
use Illuminate\Notifications\Notification;
use Madgeek\Msg91\Facades\Msg91;
use Madgeek\Msg91\Messages\SmsMessage;
use Madgeek\Msg91\Messages\WhatsAppMessage;

class OrderShipped extends Notification
{
    public function via(object $notifiable): array
    {
        return ['msg91-sms', 'msg91-whatsapp'];
    }

    public function toMsg91Sms(object $notifiable): SmsMessage
    {
        return Msg91::sms()->template('sms_template_id')->variable('VAR1', 'Ajit');
    }

    public function toMsg91Whatsapp(object $notifiable): WhatsAppMessage
    {
        return Msg91::whatsapp()->template('order_shipped')->body('Ajit');
    }
}
```

### Testing

`Msg91::fake()` records messages and does not call MSG91:

```php
Msg91::fake();

Msg91::sms()->to('9876543210')->template('sms_template_id')->send();

Msg91::assertSmsSent(fn (SmsMessage $message) => $message->to === '919876543210');
```

`Msg91::assertWhatsAppSent()` and `Msg91::assertOtpSent()` cover the other channels. `assertOtpSent()` receives an `OtpMessage` with `action` (`send`, `verify`, or `resend`), `to`, `template`, and `otp`.

Check a real configuration locally with the log driver:

```bash
php artisan msg91:test sms 9876543210 --template=sms_template_id
php artisan msg91:test whatsapp 9876543210 --template=order_shipped
php artisan msg91:test otp 9876543210
```

Successful sends dispatch `Madgeek\Msg91\Events\MessageSending` and `MessageSent`. Failures dispatch `MessageFailed` and then throw `AuthenticationException` or `RequestFailedException`.

## Contributing

Thank you for considering contributing to Laravel Msg91! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Credits

- [Ajit Das](https://github.com/ajitdas123)
- [All Contributors](../../contributors)

## License

Laravel Msg91 is open-sourced software licensed under the [MIT license](LICENSE.md).

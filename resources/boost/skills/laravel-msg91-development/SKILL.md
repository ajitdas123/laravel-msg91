---
name: laravel-msg91-development
description: >
  Configure and apply the Laravel Msg91 package in Laravel applications.
license: MIT
metadata:
  author: Ajit Das
---

# Laravel Msg91

Use this skill when a Laravel application needs to integrate the Laravel Msg91 package.

## Primary Goal

- apply the `ajit-das/laravel-msg91` package's public API in the smallest correct way

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project
- inspect the target code paths where the package should be applied

### 2. Apply the package's public API

Install `ajit-das/laravel-msg91` and publish `config/msg91.php`:

```bash
php artisan vendor:publish --tag="laravel-msg91-config"
```

Set `MSG91_AUTH_KEY`. Set `MSG91_WHATSAPP_INTEGRATED_NUMBER` and `MSG91_WHATSAPP_NAMESPACE` for WhatsApp, and `MSG91_OTP_TEMPLATE_ID` for OTP. SMS uses the template id passed to `template()`. Use `MSG91_DRIVER=log` when the app should write payloads to the log instead of calling MSG91.

Send through the `Msg91` facade:

```php
Msg91::sms()->to('9876543210')->template('sms_template_id')->variable('VAR1', 'Ajit')->send();
Msg91::whatsapp()->to('9876543210')->template('order_shipped')->body('Ajit', 'ORD-1')->send();
Msg91::otp()->to('9876543210')->send();
Msg91::otp()->verify('9876543210', '123456');
Msg91::otp()->resend('9876543210');
```

For notifications, return `msg91-sms` or `msg91-whatsapp` from `via()`. Implement `toMsg91Sms()` or `toMsg91Whatsapp()`, and `routeNotificationForMsg91Sms()` or `routeNotificationForMsg91Whatsapp()` when the message does not include `to()`.

In tests, call `Msg91::fake()` and assert with `Msg91::assertSmsSent()`, `Msg91::assertWhatsAppSent()`, or `Msg91::assertOtpSent()`. OTP assertions receive an `OtpMessage` whose `action` is `send`, `verify`, or `resend`.

To check configuration from the command line, run `php artisan msg91:test {sms|whatsapp|otp} {phone}`.

Listen for `Madgeek\Msg91\Events\MessageSent` and `Madgeek\Msg91\Events\MessageFailed` when the app needs delivery outcomes. Do not add a database table; the package does not store messages.

## Rules, References, and Templates

Read before executing:

- no additional resource files for this skill

## Examples

- Send an order-shipped SMS and WhatsApp notification by adding `msg91-sms` and `msg91-whatsapp` to the notification's `via()` method and building `SmsMessage` / `WhatsAppMessage` with the `Msg91` facade.
- Keep PHPUnit from calling MSG91 by calling `Msg91::fake()` before the code under test and asserting the normalized phone number, including the configured country code.

## Anti-patterns

- do not document package internals here; keep the skill focused on adoption in Laravel apps
- do not call MSG91 from tests when `Msg91::fake()` can record the message
- do not invent webhook, migration, or delivery-report storage; this package does not provide them

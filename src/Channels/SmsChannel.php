<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Channels;

use Illuminate\Notifications\Notification;
use Madgeek\Msg91\Exceptions\InvalidRecipientException;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Messages\SmsMessage;
use Madgeek\Msg91\Services\SmsService;

final class SmsChannel
{
    public function __construct(private readonly SmsService $sms) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $callback = [$notification, 'toMsg91Sms'];

        if (! is_callable($callback)) {
            throw new Msg91Exception('Notification ['.$notification::class.'] must define toMsg91Sms().');
        }

        $message = $callback($notifiable);

        if (! $message instanceof SmsMessage) {
            throw new Msg91Exception('Notification ['.$notification::class.'] toMsg91Sms() must return '.SmsMessage::class.'.');
        }

        if (! is_string($message->to) || $message->to === '') {
            $route = $this->route($notifiable, $notification);

            if (! is_string($route) || $route === '') {
                throw new InvalidRecipientException('No SMS recipient was provided.');
            }

            $message->to($route);
        }

        $this->sms->send($message);
    }

    private function route(object $notifiable, Notification $notification): mixed
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return null;
        }

        return $notifiable->routeNotificationFor('msg91-sms', $notification);
    }
}

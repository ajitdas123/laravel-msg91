<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Channels;

use Illuminate\Notifications\Notification;
use Madgeek\Msg91\Exceptions\InvalidRecipientException;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Messages\WhatsAppMessage;
use Madgeek\Msg91\Services\WhatsAppService;

final class WhatsAppChannel
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $callback = [$notification, 'toMsg91Whatsapp'];

        if (! is_callable($callback)) {
            throw new Msg91Exception('Notification ['.$notification::class.'] must define toMsg91Whatsapp().');
        }

        $message = $callback($notifiable);

        if (! $message instanceof WhatsAppMessage) {
            throw new Msg91Exception('Notification ['.$notification::class.'] toMsg91Whatsapp() must return '.WhatsAppMessage::class.'.');
        }

        if (! is_string($message->to) || $message->to === '') {
            $route = $this->route($notifiable, $notification);

            if (! is_string($route) || $route === '') {
                throw new InvalidRecipientException('No WhatsApp recipient was provided.');
            }

            $message->to($route);
        }

        $this->whatsapp->send($message);
    }

    private function route(object $notifiable, Notification $notification): mixed
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return null;
        }

        return $notifiable->routeNotificationFor('msg91-whatsapp', $notification);
    }
}

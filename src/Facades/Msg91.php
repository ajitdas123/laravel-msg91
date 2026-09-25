<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Facades;

use Illuminate\Support\Facades\Facade;
use Madgeek\Msg91\Credentials\Credentials;
use Madgeek\Msg91\Messages\OtpMessage;
use Madgeek\Msg91\Messages\SmsMessage;
use Madgeek\Msg91\Messages\WhatsAppMessage;
use Madgeek\Msg91\Msg91Manager;
use Madgeek\Msg91\Services\OtpService;
use Madgeek\Msg91\Services\SmsService;
use Madgeek\Msg91\Services\WhatsAppService;
use Madgeek\Msg91\Support\Delivery;
use Madgeek\Msg91\Testing\Msg91Fake;
use Madgeek\Msg91\Transport\HttpTransport;
use Madgeek\Msg91\Transport\LogTransport;
use Madgeek\Msg91\Transport\Transport;

/**
 * @method static SmsMessage sms()
 * @method static WhatsAppMessage whatsapp()
 * @method static OtpService otp()
 *
 * @see Msg91Manager
 */
final class Msg91 extends Facade
{
    /**
     * @param  (callable(SmsMessage): bool)|null  $callback
     */
    public static function assertSmsSent(?callable $callback = null): void
    {
        self::fakeTransport()->assertSmsSent($callback);
    }

    /**
     * @param  (callable(WhatsAppMessage): bool)|null  $callback
     */
    public static function assertWhatsAppSent(?callable $callback = null): void
    {
        self::fakeTransport()->assertWhatsAppSent($callback);
    }

    /**
     * @param  (callable(OtpMessage): bool)|null  $callback
     */
    public static function assertOtpSent(?callable $callback = null): void
    {
        self::fakeTransport()->assertOtpSent($callback);
    }

    public static function fake(): Msg91Fake
    {
        $fake = new Msg91Fake;

        self::swapTransport($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return Msg91Manager::class;
    }

    private static function fakeTransport(): Msg91Fake
    {
        /** @var mixed $transport */
        $transport = self::getFacadeApplication()->make(Transport::class);

        if (! $transport instanceof Msg91Fake) {
            Msg91Fake::missing();
        }

        return $transport;
    }

    private static function swapTransport(Msg91Fake $fake): void
    {
        $app = self::getFacadeApplication();

        $app->instance(Transport::class, $fake);

        foreach ([
            HttpTransport::class,
            LogTransport::class,
            Delivery::class,
            SmsService::class,
            WhatsAppService::class,
            OtpService::class,
            Msg91Manager::class,
            Credentials::class,
        ] as $abstract) {
            $app->forgetInstance($abstract);
        }

        $app->forgetInstance(Transport::class);
        $app->instance(Transport::class, $fake);

        self::clearResolvedInstance(self::getFacadeAccessor());
    }
}

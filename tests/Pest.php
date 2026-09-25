<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Tests {
    use ArrayObject;
    use Illuminate\Log\Events\MessageLogged;
    use Illuminate\Support\Facades\Log;
    use Madgeek\Msg91\Credentials\Credentials;
    use Madgeek\Msg91\Facades\Msg91;
    use Madgeek\Msg91\Msg91Manager;
    use Madgeek\Msg91\Services\OtpService;
    use Madgeek\Msg91\Services\SmsService;
    use Madgeek\Msg91\Services\WhatsAppService;
    use Madgeek\Msg91\Support\Delivery;
    use Madgeek\Msg91\Transport\HttpTransport;
    use Madgeek\Msg91\Transport\LogTransport;
    use Madgeek\Msg91\Transport\Transport;
    use ReflectionMethod;

    function resetMsg91(): void
    {
        foreach ([
            Transport::class,
            HttpTransport::class,
            LogTransport::class,
            Delivery::class,
            SmsService::class,
            WhatsAppService::class,
            OtpService::class,
            Msg91Manager::class,
            Credentials::class,
        ] as $abstract) {
            app()->forgetInstance($abstract);
        }

        $reflection = new ReflectionMethod(Msg91::class, 'clearResolvedInstance');
        $reflection->invoke(null, Msg91Manager::class);
    }

    /**
     * @return ArrayObject<int, MessageLogged>
     */
    function captureLogs(): ArrayObject
    {
        $logged = new ArrayObject;

        Log::listen(function (MessageLogged $event) use ($logged): void {
            if ($event->message === 'MSG91 message logged.') {
                $logged->append($event);
            }
        });

        return $logged;
    }
}

namespace {
    use Madgeek\Msg91\Tests\TestCase;

    uses(TestCase::class)->in(__DIR__);

    function resetMsg91(): void
    {
        Madgeek\Msg91\Tests\resetMsg91();
    }

    function captureLogs(): ArrayObject
    {
        return Madgeek\Msg91\Tests\captureLogs();
    }
}

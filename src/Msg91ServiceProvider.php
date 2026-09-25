<?php

declare(strict_types=1);

namespace Madgeek\Msg91;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Madgeek\Msg91\Channels\SmsChannel;
use Madgeek\Msg91\Channels\WhatsAppChannel;
use Madgeek\Msg91\Commands\SendTestCommand;
use Madgeek\Msg91\Credentials\Credentials;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Services\OtpService;
use Madgeek\Msg91\Services\SmsService;
use Madgeek\Msg91\Services\WhatsAppService;
use Madgeek\Msg91\Support\Delivery;
use Madgeek\Msg91\Transport\HttpTransport;
use Madgeek\Msg91\Transport\LogTransport;
use Madgeek\Msg91\Transport\Transport;
use Psr\Log\LoggerInterface;

class Msg91ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/msg91.php', 'msg91');

        $this->app->singleton(Credentials::class, function (): Credentials {
            return Credentials::fromConfig($this->configArray('msg91'));
        });

        $this->app->singleton(HttpTransport::class, function (): HttpTransport {
            return new HttpTransport(
                $this->app->make(Factory::class),
                $this->app->make(Credentials::class),
                $this->configInt('msg91.http.timeout', 10),
                $this->configInt('msg91.http.retry', 1),
            );
        });

        $this->app->singleton(LogTransport::class, function (): LogTransport {
            return new LogTransport($this->app->make(LoggerInterface::class));
        });

        $this->app->singleton(Transport::class, function (): Transport {
            $driver = $this->app->make(Repository::class)->get('msg91.driver', 'http');

            if (! is_string($driver)) {
                throw new Msg91Exception('MSG91 driver must be a string.');
            }

            return match ($driver) {
                'log' => $this->app->make(LogTransport::class),
                'http' => $this->app->make(HttpTransport::class),
                default => throw new Msg91Exception("Unsupported MSG91 driver [{$driver}]."),
            };
        });

        $this->app->singleton(Delivery::class);
        $this->app->singleton(SmsService::class);
        $this->app->singleton(WhatsAppService::class);
        $this->app->singleton(OtpService::class);
        $this->app->singleton(Msg91Manager::class);
    }

    public function boot(): void
    {
        Notification::extend('msg91-sms', fn (Container $app): SmsChannel => $app->make(SmsChannel::class));
        Notification::extend(SmsChannel::class, fn (Container $app): SmsChannel => $app->make(SmsChannel::class));
        Notification::extend('msg91-whatsapp', fn (Container $app): WhatsAppChannel => $app->make(WhatsAppChannel::class));
        Notification::extend(WhatsAppChannel::class, fn (Container $app): WhatsAppChannel => $app->make(WhatsAppChannel::class));

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/msg91.php' => config_path('msg91.php'),
        ], ['laravel-msg91', 'laravel-msg91-config']);

        $this->commands([
            SendTestCommand::class,
        ]);
    }

    /**
     * @return array<mixed, mixed>
     */
    private function configArray(string $key): array
    {
        $value = $this->app->make(Repository::class)->get($key, []);

        if (! is_array($value)) {
            return [];
        }

        return $value;
    }

    private function configInt(string $key, int $default): int
    {
        $value = $this->app->make(Repository::class)->get($key, $default);

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }
}

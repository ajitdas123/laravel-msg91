<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Commands;

use Illuminate\Console\Command;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Msg91Manager;

final class SendTestCommand extends Command
{
    protected $signature = 'msg91:test
        {channel : sms, whatsapp, or otp}
        {to : Recipient phone number}
        {--template= : SMS template id, WhatsApp template name, or OTP template id}';

    protected $description = 'Send a test SMS, WhatsApp, or OTP message through MSG91';

    public function handle(Msg91Manager $manager): int
    {
        $channel = $this->requiredString($this->argument('channel'));
        $to = $this->requiredString($this->argument('to'));

        if ($channel === null || $to === null) {
            return self::FAILURE;
        }

        try {
            match ($channel) {
                'sms' => $this->sendSms($manager, $to),
                'whatsapp' => $this->sendWhatsApp($manager, $to),
                'otp' => $this->sendOtp($manager, $to),
                default => throw new Msg91Exception('Channel must be sms, whatsapp, or otp.'),
            };
        } catch (Msg91Exception $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("MSG91 {$channel} test accepted.");

        return self::SUCCESS;
    }

    private function sendSms(Msg91Manager $manager, string $to): void
    {
        $manager->sms()
            ->to($to)
            ->template($this->optionString('template', 'test'))
            ->send();
    }

    private function sendWhatsApp(Msg91Manager $manager, string $to): void
    {
        $manager->whatsapp()
            ->to($to)
            ->template($this->optionString('template', 'test'))
            ->send();
    }

    private function sendOtp(Msg91Manager $manager, string $to): void
    {
        $otp = $manager->otp()->to($to);
        $template = $this->optionString('template', '');

        if ($template !== '') {
            $otp = $otp->template($template);
        }

        $otp->send();
    }

    private function requiredString(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            $this->components->error('Channel and recipient are required.');

            return null;
        }

        return $value;
    }

    private function optionString(string $key, string $default): string
    {
        $value = $this->option($key);

        if (! is_string($value) || $value === '') {
            return $default;
        }

        return $value;
    }
}

<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Testing;

use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Messages\OtpMessage;
use Madgeek\Msg91\Messages\SmsMessage;
use Madgeek\Msg91\Messages\WhatsAppMessage;
use Madgeek\Msg91\Support\Msg91Response;
use Madgeek\Msg91\Transport\Transport;
use PHPUnit\Framework\Assert;

final class Msg91Fake implements Transport
{
    /** @var list<SmsMessage> */
    public array $smsMessages = [];

    /** @var list<WhatsAppMessage> */
    public array $whatsappMessages = [];

    /** @var list<OtpMessage> */
    public array $otpMessages = [];

    public function send(string $endpoint, array $payload): Msg91Response
    {
        return Msg91Response::faked();
    }

    public function recordSms(SmsMessage $message): void
    {
        $this->smsMessages[] = $message;
    }

    public function recordWhatsApp(WhatsAppMessage $message): void
    {
        $this->whatsappMessages[] = $message;
    }

    public function recordOtp(string $action, string $to, ?string $template = null, ?string $otp = null): void
    {
        $this->otpMessages[] = new OtpMessage($action, $to, $template, $otp);
    }

    /**
     * @param  (callable(SmsMessage): bool)|null  $callback
     */
    public function assertSmsSent(?callable $callback = null): void
    {
        Assert::assertNotEmpty(
            $this->matchingSms($callback),
            'An MSG91 SMS message was not sent.',
        );
    }

    /**
     * @param  (callable(WhatsAppMessage): bool)|null  $callback
     */
    public function assertWhatsAppSent(?callable $callback = null): void
    {
        Assert::assertNotEmpty(
            $this->matchingWhatsApp($callback),
            'An MSG91 WhatsApp message was not sent.',
        );
    }

    /**
     * @param  (callable(OtpMessage): bool)|null  $callback
     */
    public function assertOtpSent(?callable $callback = null): void
    {
        Assert::assertNotEmpty(
            $this->matchingOtp($callback),
            'An MSG91 OTP request was not sent.',
        );
    }

    /**
     * @param  (callable(SmsMessage): bool)|null  $callback
     * @return list<SmsMessage>
     */
    private function matchingSms(?callable $callback): array
    {
        return array_values(array_filter(
            $this->smsMessages,
            static fn (SmsMessage $message): bool => $callback === null || $callback($message),
        ));
    }

    /**
     * @param  (callable(WhatsAppMessage): bool)|null  $callback
     * @return list<WhatsAppMessage>
     */
    private function matchingWhatsApp(?callable $callback): array
    {
        return array_values(array_filter(
            $this->whatsappMessages,
            static fn (WhatsAppMessage $message): bool => $callback === null || $callback($message),
        ));
    }

    /**
     * @param  (callable(OtpMessage): bool)|null  $callback
     * @return list<OtpMessage>
     */
    private function matchingOtp(?callable $callback): array
    {
        return array_values(array_filter(
            $this->otpMessages,
            static fn (OtpMessage $message): bool => $callback === null || $callback($message),
        ));
    }

    public static function missing(): never
    {
        throw new Msg91Exception('Msg91::fake() was not called.');
    }
}

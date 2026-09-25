<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Services;

use Madgeek\Msg91\Credentials\Credentials;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Support\Delivery;
use Madgeek\Msg91\Support\Msg91Response;
use Madgeek\Msg91\Support\PhoneNumber;
use Madgeek\Msg91\Testing\Msg91Fake;

final class OtpService
{
    private ?string $mobile = null;

    private ?string $templateId = null;

    public function __construct(
        private readonly Delivery $delivery,
        private readonly Credentials $credentials,
    ) {}

    public function to(string $mobile): self
    {
        $pending = clone $this;
        $pending->mobile = $mobile;

        return $pending;
    }

    public function template(string $templateId): self
    {
        $pending = clone $this;
        $pending->templateId = $templateId;

        return $pending;
    }

    public function send(): Msg91Response
    {
        return $this->dispatch('send', $this->mobile, $this->templateId ?? $this->credentials->otpTemplateId, null, [
            'otp_expiry' => $this->credentials->otpExpiry,
            'otp_length' => $this->credentials->otpLength,
        ], 'otp');
    }

    public function verify(string $mobile, string $otp): Msg91Response
    {
        return $this->dispatch('verify', $mobile, null, $otp, [
            'otp' => $otp,
        ], 'otp_verify');
    }

    public function resend(string $mobile, string $retryType = 'text'): Msg91Response
    {
        return $this->dispatch('resend', $mobile, null, null, [
            'retrytype' => $retryType,
        ], 'otp_retry');
    }

    /**
     * @param  array<string, int|string>  $extra
     */
    private function dispatch(
        string $action,
        ?string $mobile,
        ?string $templateId,
        ?string $otp,
        array $extra,
        string $endpoint,
    ): Msg91Response {
        if (! is_string($mobile) || $mobile === '') {
            throw new Msg91Exception('OTP recipient is required.');
        }

        $phone = PhoneNumber::from($mobile, $this->credentials->countryCode);

        /** @var array<string, int|string> $payload */
        $payload = ['mobile' => $phone->number, ...$extra];

        if ($action === 'send') {
            if (! is_string($templateId) || $templateId === '') {
                throw new Msg91Exception('OTP template id is required.');
            }

            $payload['template_id'] = $templateId;
        }

        $response = $this->delivery->send('otp', $this->credentials->endpoint($endpoint), $payload);

        $transport = $this->delivery->transport();

        if ($transport instanceof Msg91Fake) {
            $transport->recordOtp($action, $phone->number, $templateId, $otp);
        }

        return $response;
    }
}

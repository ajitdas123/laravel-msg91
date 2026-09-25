<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Services;

use Madgeek\Msg91\Credentials\Credentials;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Messages\SmsMessage;
use Madgeek\Msg91\Support\Delivery;
use Madgeek\Msg91\Support\Msg91Response;
use Madgeek\Msg91\Support\PhoneNumber;
use Madgeek\Msg91\Testing\Msg91Fake;

final class SmsService
{
    public function __construct(
        private readonly Delivery $delivery,
        private readonly Credentials $credentials,
    ) {}

    public function send(SmsMessage $message): Msg91Response
    {
        $phone = PhoneNumber::from($message->to ?? '', $this->credentials->countryCode);
        $message->to($phone->number);

        $templateId = $message->templateId;

        if (! is_string($templateId) || $templateId === '') {
            throw new Msg91Exception('SMS template id is required.');
        }

        $recipient = $message->variables;
        $recipient['mobiles'] = $phone->number;

        $payload = [
            'template_id' => $templateId,
            'recipients' => [$recipient],
        ];

        if (is_bool($message->shortUrl)) {
            $payload['short_url'] = $message->shortUrl ? '1' : '0';
        }

        if (is_int($message->shortUrlExpiry)) {
            $payload['short_url_expiry'] = (string) $message->shortUrlExpiry;
        }

        if ($message->realTimeResponse) {
            $payload['realTimeResponse'] = '1';
        }

        $response = $this->delivery->send('sms', $this->credentials->endpoint('sms'), $payload);

        $transport = $this->delivery->transport();

        if ($transport instanceof Msg91Fake) {
            $transport->recordSms($message);
        }

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace Madgeek\Msg91;

use Madgeek\Msg91\Messages\SmsMessage;
use Madgeek\Msg91\Messages\WhatsAppMessage;
use Madgeek\Msg91\Services\OtpService;
use Madgeek\Msg91\Services\SmsService;
use Madgeek\Msg91\Services\WhatsAppService;

final class Msg91Manager
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly WhatsAppService $whatsapp,
        private readonly OtpService $otp,
    ) {}

    public function sms(): SmsMessage
    {
        return new SmsMessage($this->sms);
    }

    public function whatsapp(): WhatsAppMessage
    {
        return new WhatsAppMessage($this->whatsapp);
    }

    public function otp(): OtpService
    {
        return clone $this->otp;
    }
}

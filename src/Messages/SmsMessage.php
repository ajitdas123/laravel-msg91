<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Messages;

use Madgeek\Msg91\Services\SmsService;
use Madgeek\Msg91\Support\Msg91Response;

final class SmsMessage
{
    public ?string $to = null;

    public ?string $templateId = null;

    public ?bool $shortUrl = null;

    public ?int $shortUrlExpiry = null;

    public bool $realTimeResponse = false;

    /** @var array<string, string> */
    public array $variables = [];

    public function __construct(private readonly SmsService $service) {}

    public function to(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function template(string $templateId): self
    {
        $this->templateId = $templateId;

        return $this;
    }

    public function shortUrl(bool $enabled = true): self
    {
        $this->shortUrl = $enabled;

        return $this;
    }

    public function shortUrlExpiry(int $seconds): self
    {
        $this->shortUrlExpiry = $seconds;

        return $this;
    }

    public function realTimeResponse(bool $enabled = true): self
    {
        $this->realTimeResponse = $enabled;

        return $this;
    }

    public function variable(string $key, string $value): self
    {
        $this->variables[$key] = $value;

        return $this;
    }

    public function send(): Msg91Response
    {
        return $this->service->send($this);
    }
}

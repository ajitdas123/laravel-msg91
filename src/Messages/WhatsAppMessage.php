<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Messages;

use Madgeek\Msg91\Services\WhatsAppService;
use Madgeek\Msg91\Support\Msg91Response;

final class WhatsAppMessage
{
    public ?string $to = null;

    public ?string $template = null;

    public ?string $language = null;

    public ?string $namespace = null;

    public ?string $integratedNumber = null;

    /** @var array<string, array<string, string>> */
    public array $components = [];

    public function __construct(private readonly WhatsAppService $service) {}

    public function to(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function template(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function language(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function namespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function from(string $integratedNumber): self
    {
        $this->integratedNumber = $integratedNumber;

        return $this;
    }

    public function body(string ...$values): self
    {
        foreach (array_keys($this->components) as $key) {
            if (str_starts_with($key, 'body_')) {
                unset($this->components[$key]);
            }
        }

        foreach (array_values($values) as $index => $value) {
            $this->components['body_'.($index + 1)] = [
                'type' => 'text',
                'value' => $value,
            ];
        }

        return $this;
    }

    public function header(string $value, string $type = 'text'): self
    {
        $this->components['header_1'] = [
            'type' => $type,
            'value' => $value,
        ];

        return $this;
    }

    public function button(string $value, int $index = 1, string $subtype = 'url'): self
    {
        $this->components['button_'.$index] = [
            'subtype' => $subtype,
            'type' => 'text',
            'value' => $value,
        ];

        return $this;
    }

    public function send(): Msg91Response
    {
        return $this->service->send($this);
    }
}

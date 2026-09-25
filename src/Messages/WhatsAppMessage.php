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

    public string $kind = 'template';

    public ?string $text = null;

    public ?string $interactiveBody = null;

    public ?string $interactiveFooter = null;

    /** @var array{type: string, text?: string, image?: array{link: string}}|null */
    public ?array $interactiveHeader = null;

    /** @var list<array{type: string, reply: array{id: string, title: string}}> */
    public array $replies = [];

    public ?string $listButton = null;

    /** @var list<array{title: string, rows: list<array{id: string, title: string, description?: string}>}> */
    public array $sections = [];

    public ?string $catalogId = null;

    public ?string $productRetailerId = null;

    /** @var list<array{title: string, product_items: list<array{product_retailer_id: string}>}> */
    public array $productSections = [];

    /** @var list<array{name: string, amount: string, quantity: string}> */
    public array $items = [];

    public function __construct(private readonly WhatsAppService $service) {}

    public function to(string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function template(string $template): self
    {
        $this->kind = 'template';
        $this->template = $template;

        return $this;
    }

    public function text(string $text): self
    {
        $this->kind = 'text';
        $this->text = $text;

        return $this;
    }

    public function buttons(string $body): self
    {
        $this->kind = 'buttons';
        $this->interactiveBody = $body;

        return $this;
    }

    public function reply(string $id, string $title): self
    {
        $this->replies[] = [
            'type' => 'reply',
            'reply' => [
                'id' => $id,
                'title' => $title,
            ],
        ];

        return $this;
    }

    public function listMessage(string $body, string $button): self
    {
        $this->kind = 'list';
        $this->interactiveBody = $body;
        $this->listButton = $button;

        return $this;
    }

    /**
     * @param  list<array{id: string, title: string, description?: string}>  $rows
     */
    public function section(string $title, array $rows): self
    {
        $normalized = [];

        foreach ($rows as $row) {
            $item = [
                'id' => $row['id'],
                'title' => $row['title'],
            ];

            if (isset($row['description']) && $row['description'] !== '') {
                $item['description'] = $row['description'];
            }

            $normalized[] = $item;
        }

        $this->sections[] = [
            'title' => $title,
            'rows' => $normalized,
        ];

        return $this;
    }

    public function requestLocation(string $body): self
    {
        $this->kind = 'location';
        $this->interactiveBody = $body;

        return $this;
    }

    public function product(string $catalogId, string $retailerId, string $body): self
    {
        $this->kind = 'product';
        $this->catalogId = $catalogId;
        $this->productRetailerId = $retailerId;
        $this->interactiveBody = $body;

        return $this;
    }

    public function productList(string $catalogId, string $body): self
    {
        $this->kind = 'product_list';
        $this->catalogId = $catalogId;
        $this->interactiveBody = $body;

        return $this;
    }

    public function products(string $title, string ...$retailerIds): self
    {
        $this->productSections[] = [
            'title' => $title,
            'product_items' => array_map(
                static fn (string $retailerId): array => ['product_retailer_id' => $retailerId],
                array_values($retailerIds),
            ),
        ];

        return $this;
    }

    public function payment(string $body): self
    {
        $this->kind = 'payment';
        $this->interactiveBody = $body;

        return $this;
    }

    public function item(string $name, int|string $amount, int|string $quantity = 1): self
    {
        $this->items[] = [
            'name' => $name,
            'amount' => (string) $amount,
            'quantity' => (string) $quantity,
        ];

        return $this;
    }

    public function heading(string $text): self
    {
        $this->interactiveHeader = [
            'type' => 'text',
            'text' => $text,
        ];

        return $this;
    }

    public function headingImage(string $url): self
    {
        $this->interactiveHeader = [
            'type' => 'image',
            'image' => [
                'link' => $url,
            ],
        ];

        return $this;
    }

    public function footnote(string $text): self
    {
        $this->interactiveFooter = $text;

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

    public function balance(): Msg91Response
    {
        return $this->service->balance($this->integratedNumber);
    }
}

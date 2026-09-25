<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Services;

use Madgeek\Msg91\Credentials\Credentials;
use Madgeek\Msg91\Exceptions\Msg91Exception;
use Madgeek\Msg91\Messages\WhatsAppMessage;
use Madgeek\Msg91\Support\Delivery;
use Madgeek\Msg91\Support\Msg91Response;
use Madgeek\Msg91\Support\PhoneNumber;
use Madgeek\Msg91\Testing\Msg91Fake;
use stdClass;

final class WhatsAppService
{
    public function __construct(
        private readonly Delivery $delivery,
        private readonly Credentials $credentials,
    ) {}

    public function send(WhatsAppMessage $message): Msg91Response
    {
        $response = match ($message->kind) {
            'text' => $this->sendText($message),
            'buttons' => $this->sendInteractive($message, $this->buttons($message)),
            'list' => $this->sendInteractive($message, $this->list($message)),
            'location' => $this->sendInteractive($message, $this->location($message)),
            'product' => $this->sendInteractive($message, $this->product($message)),
            'product_list' => $this->sendInteractive($message, $this->productList($message)),
            'payment' => $this->sendInteractive($message, $this->payment($message)),
            default => $this->sendTemplate($message),
        };

        $transport = $this->delivery->transport();

        if ($transport instanceof Msg91Fake) {
            $transport->recordWhatsApp($message);
        }

        return $response;
    }

    public function balance(?string $integratedNumber = null): Msg91Response
    {
        return $this->delivery->send('whatsapp', $this->credentials->endpoint('whatsapp_balance'), [
            'integrated_number' => $this->integratedNumber($integratedNumber),
            'service' => 'whatsapp',
        ]);
    }

    private function sendTemplate(WhatsAppMessage $message): Msg91Response
    {
        $phone = $this->recipient($message);
        $template = $message->template;

        if (! is_string($template) || $template === '') {
            throw new Msg91Exception('WhatsApp template name is required.');
        }

        $namespace = $message->namespace ?? $this->credentials->whatsappNamespace;

        if (! is_string($namespace) || $namespace === '') {
            throw new Msg91Exception('WhatsApp template namespace is required.');
        }

        $language = $message->language ?? $this->credentials->whatsappLanguage;

        return $this->delivery->send('whatsapp', $this->credentials->endpoint('whatsapp'), [
            'integrated_number' => $this->integratedNumber($message->integratedNumber),
            'content_type' => 'template',
            'payload' => [
                'messaging_product' => 'whatsapp',
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => [
                        'code' => $language,
                        'policy' => 'deterministic',
                    ],
                    'namespace' => $namespace,
                    'to_and_components' => [
                        [
                            'to' => [$phone],
                            'components' => $message->components === [] ? new stdClass : $message->components,
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function sendText(WhatsAppMessage $message): Msg91Response
    {
        $text = $message->text;

        if (! is_string($text) || $text === '') {
            throw new Msg91Exception('WhatsApp text is required.');
        }

        $payload = [
            'integrated_number' => $this->integratedNumber($message->integratedNumber),
            'recipient_number' => $this->recipient($message),
            'content_type' => 'text',
            'text' => $text,
        ];

        $endpoint = $this->credentials->endpoint('whatsapp_session');
        $separator = str_contains($endpoint, '?') ? '&' : '?';

        return $this->delivery->send(
            'whatsapp',
            $endpoint.$separator.http_build_query($payload, '', '&', PHP_QUERY_RFC3986),
            $payload,
        );
    }

    /**
     * @param  array<string, mixed>  $interactive
     */
    private function sendInteractive(WhatsAppMessage $message, array $interactive): Msg91Response
    {
        return $this->delivery->send('whatsapp', $this->credentials->endpoint('whatsapp_session'), [
            'recipient_number' => $this->recipient($message),
            'integrated_number' => $this->integratedNumber($message->integratedNumber),
            'content_type' => 'interactive',
            'interactive' => $interactive,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buttons(WhatsAppMessage $message): array
    {
        $body = $this->requireBody($message, 'WhatsApp button body is required.');

        if ($message->replies === []) {
            throw new Msg91Exception('A WhatsApp button message needs at least one reply.');
        }

        if (count($message->replies) > 3) {
            throw new Msg91Exception('A WhatsApp button message accepts at most 3 replies.');
        }

        foreach ($message->replies as $reply) {
            if ($reply['reply']['id'] === '' || $reply['reply']['title'] === '') {
                throw new Msg91Exception('WhatsApp reply buttons need an id and a title.');
            }
        }

        return $this->interactive('button', $message, $body, [
            'buttons' => $message->replies,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function list(WhatsAppMessage $message): array
    {
        $body = $this->requireBody($message, 'WhatsApp list body is required.');

        $button = $message->listButton;

        if (! is_string($button) || $button === '') {
            throw new Msg91Exception('WhatsApp list button label is required.');
        }

        if ($message->sections === []) {
            throw new Msg91Exception('A WhatsApp list needs at least one section.');
        }

        foreach ($message->sections as $section) {
            if ($section['title'] === '' || $section['rows'] === []) {
                throw new Msg91Exception('A WhatsApp list section needs a title and at least one row.');
            }

            foreach ($section['rows'] as $row) {
                if ($row['id'] === '' || $row['title'] === '') {
                    throw new Msg91Exception('WhatsApp list rows need an id and a title.');
                }
            }
        }

        return $this->interactive('list', $message, $body, [
            'button' => $button,
            'sections' => $message->sections,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function location(WhatsAppMessage $message): array
    {
        $body = $this->requireBody($message, 'WhatsApp location prompt is required.');

        return [
            'type' => 'location_request_message',
            'body' => [
                'text' => $body,
            ],
            'action' => [
                'name' => 'send_location',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function product(WhatsAppMessage $message): array
    {
        $body = $this->requireBody($message, 'WhatsApp product body is required.');
        $catalogId = $this->requireCatalog($message);

        $retailerId = $message->productRetailerId;

        if (! is_string($retailerId) || $retailerId === '') {
            throw new Msg91Exception('WhatsApp product id is required.');
        }

        return $this->interactive('product', $message, $body, [
            'catalog_id' => $catalogId,
            'product_retailer_id' => $retailerId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productList(WhatsAppMessage $message): array
    {
        $body = $this->requireBody($message, 'WhatsApp product list body is required.');
        $catalogId = $this->requireCatalog($message);

        if (($message->interactiveHeader['type'] ?? null) !== 'text') {
            throw new Msg91Exception('WhatsApp product list header is required.');
        }

        if ($message->productSections === []) {
            throw new Msg91Exception('A WhatsApp product list needs at least one product.');
        }

        foreach ($message->productSections as $section) {
            if ($section['title'] === '' || $section['product_items'] === []) {
                throw new Msg91Exception('A WhatsApp product section needs a title and at least one product.');
            }

            foreach ($section['product_items'] as $item) {
                if ($item['product_retailer_id'] === '') {
                    throw new Msg91Exception('WhatsApp product id is required.');
                }
            }
        }

        return $this->interactive('product_list', $message, $body, [
            'catalog_id' => $catalogId,
            'sections' => $message->productSections,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payment(WhatsAppMessage $message): array
    {
        $body = $this->requireBody($message, 'WhatsApp payment body is required.');

        if ($message->items === []) {
            throw new Msg91Exception('A WhatsApp payment link needs at least one item.');
        }

        foreach ($message->items as $item) {
            if ($item['name'] === '' || $item['amount'] === '' || $item['quantity'] === '') {
                throw new Msg91Exception('WhatsApp payment items need a name, amount, and quantity.');
            }
        }

        $interactive = [
            'type' => 'payment_link',
        ];

        if (is_array($message->interactiveHeader)) {
            $interactive['header'] = $message->interactiveHeader;
        }

        $interactive['body'] = [
            'text' => $body,
        ];

        if (is_string($message->interactiveFooter) && $message->interactiveFooter !== '') {
            $interactive['footer'] = [
                'text' => $message->interactiveFooter,
            ];
        }

        $interactive['items'] = $message->items;

        return $interactive;
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>
     */
    private function interactive(string $type, WhatsAppMessage $message, string $body, array $action): array
    {
        $interactive = [
            'type' => $type,
        ];

        if (is_array($message->interactiveHeader)) {
            $interactive['header'] = $message->interactiveHeader;
        }

        $interactive['body'] = [
            'text' => $body,
        ];

        if (is_string($message->interactiveFooter) && $message->interactiveFooter !== '') {
            $interactive['footer'] = [
                'text' => $message->interactiveFooter,
            ];
        }

        $interactive['action'] = $action;

        return $interactive;
    }

    private function requireBody(WhatsAppMessage $message, string $error): string
    {
        if (! is_string($message->interactiveBody) || $message->interactiveBody === '') {
            throw new Msg91Exception($error);
        }

        return $message->interactiveBody;
    }

    private function requireCatalog(WhatsAppMessage $message): string
    {
        if (! is_string($message->catalogId) || $message->catalogId === '') {
            throw new Msg91Exception('WhatsApp catalog id is required.');
        }

        return $message->catalogId;
    }

    private function recipient(WhatsAppMessage $message): string
    {
        $phone = PhoneNumber::from($message->to ?? '', $this->credentials->countryCode);
        $message->to($phone->number);

        return $phone->number;
    }

    private function integratedNumber(?string $integratedNumber): string
    {
        $number = $integratedNumber ?? $this->credentials->whatsappIntegratedNumber;

        if (! is_string($number) || $number === '') {
            throw new Msg91Exception('WhatsApp integrated number is required.');
        }

        return $number;
    }
}

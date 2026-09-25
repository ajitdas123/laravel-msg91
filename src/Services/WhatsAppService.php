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
        $phone = PhoneNumber::from($message->to ?? '', $this->credentials->countryCode);
        $message->to($phone->number);

        $template = $message->template;

        if (! is_string($template) || $template === '') {
            throw new Msg91Exception('WhatsApp template name is required.');
        }

        $integratedNumber = $message->integratedNumber ?? $this->credentials->whatsappIntegratedNumber;

        if (! is_string($integratedNumber) || $integratedNumber === '') {
            throw new Msg91Exception('WhatsApp integrated number is required.');
        }

        $namespace = $message->namespace ?? $this->credentials->whatsappNamespace;

        if (! is_string($namespace) || $namespace === '') {
            throw new Msg91Exception('WhatsApp template namespace is required.');
        }

        $language = $message->language ?? $this->credentials->whatsappLanguage;

        $response = $this->delivery->send('whatsapp', $this->credentials->endpoint('whatsapp'), [
            'integrated_number' => $integratedNumber,
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
                            'to' => [$phone->number],
                            'components' => $message->components === [] ? new stdClass : $message->components,
                        ],
                    ],
                ],
            ],
        ]);

        $transport = $this->delivery->transport();

        if ($transport instanceof Msg91Fake) {
            $transport->recordWhatsApp($message);
        }

        return $response;
    }
}

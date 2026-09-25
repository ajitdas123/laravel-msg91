<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Credentials;

use Madgeek\Msg91\Exceptions\Msg91Exception;

final readonly class Credentials
{
    /**
     * @param  array<string, string>  $endpoints
     */
    public function __construct(
        public string $authKey,
        public string $countryCode,
        public ?string $senderId,
        public ?string $whatsappIntegratedNumber,
        public ?string $whatsappNamespace,
        public string $whatsappLanguage,
        public ?string $otpTemplateId,
        public int $otpExpiry,
        public int $otpLength,
        public array $endpoints,
    ) {}

    /**
     * @param  array<mixed, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $whatsapp = is_array($config['whatsapp'] ?? null) ? $config['whatsapp'] : [];
        $otp = is_array($config['otp'] ?? null) ? $config['otp'] : [];

        return new self(
            authKey: self::stringValue($config['auth_key'] ?? null),
            countryCode: self::stringValue($config['country_code'] ?? null, '91'),
            senderId: self::nullableString($config['sender_id'] ?? null),
            whatsappIntegratedNumber: self::nullableString($whatsapp['integrated_number'] ?? null),
            whatsappNamespace: self::nullableString($whatsapp['namespace'] ?? null),
            whatsappLanguage: self::stringValue($whatsapp['language'] ?? null, 'en'),
            otpTemplateId: self::nullableString($otp['template_id'] ?? null),
            otpExpiry: self::intValue($otp['expiry'] ?? null, 5),
            otpLength: self::intValue($otp['length'] ?? null, 6),
            endpoints: self::endpoints($config['endpoints'] ?? null),
        );
    }

    public function endpoint(string $name): string
    {
        $endpoint = $this->endpoints[$name] ?? '';

        if ($endpoint === '') {
            throw new Msg91Exception("MSG91 endpoint [{$name}] is not configured.");
        }

        return $endpoint;
    }

    /**
     * @return array<string, string>
     */
    private static function endpoints(mixed $configured): array
    {
        if (! is_array($configured)) {
            return [];
        }

        $endpoints = [];

        foreach ($configured as $name => $endpoint) {
            if (! is_string($name) || ! is_string($endpoint) || $endpoint === '') {
                continue;
            }

            $endpoints[$name] = $endpoint;
        }

        return $endpoints;
    }

    private static function stringValue(mixed $value, string $default = ''): string
    {
        if (! is_string($value) || $value === '') {
            return $default;
        }

        return $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private static function intValue(mixed $value, int $default): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }
}

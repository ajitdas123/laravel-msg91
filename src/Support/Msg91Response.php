<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Support;

final readonly class Msg91Response
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        public bool $successful,
        public int $status,
        public array $body,
        public ?string $requestId,
        public ?string $message,
    ) {}

    /**
     * @param  array<string, mixed>  $body
     */
    public static function fromHttp(int $status, array $body): self
    {
        $type = $body['type'] ?? null;
        $message = isset($body['message']) && is_string($body['message']) ? $body['message'] : null;

        return new self(
            successful: $status >= 200 && $status < 300 && $type !== 'error',
            status: $status,
            body: $body,
            requestId: self::requestId($body),
            message: $message,
        );
    }

    public static function logged(): self
    {
        return new self(true, 200, ['type' => 'success', 'message' => 'logged'], null, 'logged');
    }

    public static function faked(): self
    {
        return new self(true, 200, ['type' => 'success', 'message' => 'faked'], null, 'faked');
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private static function requestId(array $body): ?string
    {
        foreach (['request_id', 'requestId'] as $key) {
            if (! array_key_exists($key, $body)) {
                continue;
            }

            $value = $body[$key];

            if (is_string($value) || is_int($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}

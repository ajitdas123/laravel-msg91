<?php

declare(strict_types=1);

namespace Madgeek\Msg91\Transport;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Madgeek\Msg91\Credentials\Credentials;
use Madgeek\Msg91\Exceptions\AuthenticationException;
use Madgeek\Msg91\Exceptions\RequestFailedException;
use Madgeek\Msg91\Support\Msg91Response;

final class HttpTransport implements Transport
{
    public function __construct(
        private readonly Factory $http,
        private readonly Credentials $credentials,
        private readonly int $timeout,
        private readonly int $retry,
    ) {}

    public function send(string $endpoint, array $payload): Msg91Response
    {
        if ($this->credentials->authKey === '') {
            throw new AuthenticationException('MSG91 auth key is not configured.');
        }

        $pending = $this->http
            ->withHeaders([
                'authkey' => $this->credentials->authKey,
                'accept' => 'application/json',
            ])
            ->asJson()
            ->timeout($this->timeout);

        if ($this->retry > 0) {
            $pending = $pending->retry($this->retry, 100);
        }

        try {
            $response = $pending->post($endpoint, $payload);
        } catch (ConnectionException $exception) {
            throw new RequestFailedException(
                $exception->getMessage(),
                new Msg91Response(false, 0, ['message' => $exception->getMessage()], null, $exception->getMessage()),
            );
        }

        $decoded = $response->json();
        $body = is_array($decoded) ? $this->stringKeys($decoded) : ['message' => $response->body()];
        $msg91Response = Msg91Response::fromHttp($response->status(), $body);

        if (! $msg91Response->successful && $this->isAuthenticationFailure($msg91Response)) {
            throw new AuthenticationException(
                $msg91Response->message ?? 'MSG91 authentication failed.',
                $msg91Response,
            );
        }

        if (! $msg91Response->successful) {
            throw new RequestFailedException(
                $msg91Response->message ?? 'MSG91 request failed.',
                $msg91Response,
            );
        }

        return $msg91Response;
    }

    /**
     * @param  array<mixed, mixed>  $body
     * @return array<string, mixed>
     */
    private function stringKeys(array $body): array
    {
        $normalized = [];

        foreach ($body as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }

    private function isAuthenticationFailure(Msg91Response $response): bool
    {
        if (in_array($response->status, [401, 403], true)) {
            return true;
        }

        return str_contains(strtolower($response->message ?? ''), 'auth');
    }
}

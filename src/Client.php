<?php

declare(strict_types=1);

namespace Inboxili;

use Inboxili\Http\CurlTransport;
use Inboxili\Http\Transport;

/**
 * Client for the Inboxili transactional email API.
 *
 * Only HTTP 429 is retried (up to $maxRetries times) because the request was rejected before sending.
 * Timeouts and 5xx are never retried: the API has no idempotency key, so a retry could send a duplicate.
 */
final class Client
{
    public const VERSION = '0.1.1';
    public const DEFAULT_BASE_URL = 'https://api.inboxili.com/api/v1';

    public readonly Emails $emails;
    private string $baseUrl;
    private Transport $transport;

    public function __construct(
        private string $apiKey,
        string $baseUrl = self::DEFAULT_BASE_URL,
        private float $timeout = 10.0,
        private int $maxRetries = 2,
        ?Transport $transport = null,
        private ?\Closure $sleep = null,
    ) {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('apiKey is required.');
        }
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->transport = $transport ?? new CurlTransport();
        $this->emails = new Emails($this);
    }

    public function __debugInfo(): array
    {
        return ['baseUrl' => $this->baseUrl]; // never expose the key in var_dump
    }

    /**
     * @internal
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function post(string $path, array $payload): array
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'User-Agent' => 'inboxili-php/' . self::VERSION,
        ];

        for ($attempt = 0;; $attempt++) {
            [$status, $raw] = $this->transport->post($this->baseUrl . $path, $headers, $json, $this->timeout);
            $body = json_decode($raw, true);
            $body = is_array($body) ? $body : [];

            if ($status >= 200 && $status < 300) {
                return $body;
            }
            if ($status === 429 && $attempt < $this->maxRetries) {
                $seconds = 2 ** $attempt + mt_rand(0, 250) / 1000;
                $this->sleep !== null ? ($this->sleep)($seconds) : usleep((int) ($seconds * 1_000_000));
                continue;
            }

            throw new InboxiliException(
                $status,
                (string) ($body['error']['code'] ?? 'http_error'),
                (string) ($body['error']['message'] ?? "HTTP $status"),
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Inboxili\Http;

use Inboxili\ConnectionException;

final class CurlTransport implements Transport
{
    public function post(string $url, array $headers, string $body, float $timeout): array
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = "$name: $value";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => (int) round($timeout * 1000),
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_POSTFIELDS => $body,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        // No curl_close(): the handle is freed automatically, and the function is deprecated as of PHP 8.5.

        if ($raw === false || $status === 0) {
            throw new ConnectionException('No response from Inboxili. The email may or may not have been sent. ' . $error);
        }

        return [$status, (string) $raw];
    }
}

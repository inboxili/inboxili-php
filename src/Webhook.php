<?php

declare(strict_types=1);

namespace Inboxili;

final class Webhook
{
    /**
     * Verify an X-Inboxili-Signature header. $rawBody must be the exact bytes received
     * (file_get_contents('php://input')), not re-encoded JSON. The signature is the hex HMAC-SHA256 of the body.
     */
    public static function verify(string $rawBody, string $signature, string $secret): bool
    {
        return hash_equals(hash_hmac('sha256', $rawBody, $secret), strtolower(trim($signature)));
    }
}

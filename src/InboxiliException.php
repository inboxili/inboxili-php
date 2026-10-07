<?php

declare(strict_types=1);

namespace Inboxili;

/** Raised for any non-2xx response from the Inboxili API. */
class InboxiliException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        /** Machine-readable code from the API, for example "sender_not_verified". */
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message, $status);
    }
}

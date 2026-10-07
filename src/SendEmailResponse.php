<?php

declare(strict_types=1);

namespace Inboxili;

final class SendEmailResponse
{
    public function __construct(
        /** "sent" means the delivery provider accepted the message, not that it reached the inbox. */
        public readonly string $status,
        public readonly ?string $messageId,
    ) {
    }
}

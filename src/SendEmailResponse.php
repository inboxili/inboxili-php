<?php

declare(strict_types=1);

namespace Inboxili;

final class SendEmailResponse
{
    public function __construct(
        /**
         * "sent": the delivery provider accepted the message (HTTP 200), which is not the same as reaching the inbox.
         * "queued": the key is sending faster than 100 emails a minute, so the email was accepted (HTTP 202) and will be
         * delivered in order shortly. Treat it as success and do not send it again.
         */
        public readonly string $status,
        /** Null while queued. */
        public readonly ?string $messageId,
        /** Set when queued. It appears as `queue_id` on this email's later webhook events. */
        public readonly ?string $queueId = null,
    ) {
    }
}

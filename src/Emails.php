<?php

declare(strict_types=1);

namespace Inboxili;

final class Emails
{
    public function __construct(private Client $client)
    {
    }

    /**
     * Send one transactional email.
     *
     * Provide either `html_body` or `template_id`; `subject` is required unless a template is used.
     * `text_body` does not get merge tags applied by the API.
     *
     * @param array{
     *   to: string,
     *   from_email: string,
     *   from_name?: string,
     *   subject?: string,
     *   html_body?: string,
     *   text_body?: string,
     *   template_id?: string,
     *   template_data?: array<string,scalar>
     * } $message
     *
     * @throws InboxiliException      on a non-2xx API response
     * @throws ConnectionException    when no response is received
     * @throws \InvalidArgumentException on a request that cannot be valid
     */
    public function send(array $message): SendEmailResponse
    {
        foreach (['to', 'from_email'] as $required) {
            if (empty($message[$required])) {
                throw new \InvalidArgumentException("'$required' is required.");
            }
        }
        if (empty($message['html_body']) && empty($message['template_id'])) {
            throw new \InvalidArgumentException("Provide either 'html_body' or 'template_id'.");
        }
        if (empty($message['template_id']) && empty($message['subject'])) {
            throw new \InvalidArgumentException("'subject' is required when not using a template.");
        }

        $allowed = ['to', 'from_email', 'from_name', 'subject', 'html_body', 'text_body', 'template_id', 'template_data'];
        $unknown = array_diff(array_keys($message), $allowed);
        if ($unknown) {
            throw new \InvalidArgumentException('Unknown field(s): ' . implode(', ', $unknown));
        }

        $body = $this->client->post('/transactional/send', array_filter($message, static fn ($v) => $v !== null));

        return new SendEmailResponse((string) ($body['status'] ?? ''), $body['message_id'] ?? null);
    }
}

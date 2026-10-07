# inboxili/inboxili (PHP)

PHP client for the [Inboxili](https://inboxili.com) transactional email API. PHP 8.1+, needs the `curl` and `json` extensions, no other runtime dependencies.

```bash
composer require inboxili/inboxili
```

## Send an email

```php
<?php
require 'vendor/autoload.php';

use Inboxili\Client;

$inboxili = new Client(getenv('INBOXILI_API_KEY'));

$result = $inboxili->emails->send([
    'to' => 'ada@example.com',
    'from_email' => 'hello@yourdomain.com', // must be a sender on a verified domain
    'from_name' => 'Acme',
    'subject' => 'Welcome, {{first_name}}',
    'html_body' => '<p>Hi {{first_name}}, your account is ready.</p>',
    'text_body' => 'Hi Ada, your account is ready.',
    'template_data' => ['first_name' => 'Ada'],
]);

echo $result->status, ' ', $result->messageId, PHP_EOL;
```

Field names are the API's own. `status` of `sent` means the delivery provider accepted the message, not that it reached the inbox. Use webhooks for delivery events.

### From a template

```php
$inboxili->emails->send([
    'to' => 'ada@example.com',
    'from_email' => 'hello@yourdomain.com',
    'template_id' => '00000000-0000-0000-0000-000000000000',
    'template_data' => ['first_name' => 'Ada'],
]);
```

## Authentication

Create an API key in the dashboard under Settings, Developer, with the `transactional:send` scope. Load it from the environment. Never commit it.

## Options

```php
new Client(string $apiKey, string $baseUrl = 'https://api.inboxili.com/api/v1', float $timeout = 10.0, int $maxRetries = 2);
```

## Error handling

```php
use Inboxili\InboxiliException;
use Inboxili\ConnectionException;

try {
    $inboxili->emails->send([...]);
} catch (InboxiliException $e) {
    error_log("{$e->status} {$e->errorCode}: {$e->getMessage()}"); // e.g. 422 sender_not_verified
} catch (ConnectionException $e) {
    // No response. The email may or may not have been sent.
} catch (\InvalidArgumentException $e) {
    // The request could not be valid; nothing was sent.
}
```

| Status | `errorCode` | Meaning |
|---|---|---|
| 401 | `unauthorized` | Missing, invalid, revoked or expired key |
| 403 | `forbidden` | Missing scope, or IP not allowed |
| 422 | `validation_error`, `sender_not_verified`, `send_failed` | Bad request, unverified sender, or provider rejection |
| 429 | `rate_limited` | Over 120 requests per minute for this key |

**Retries.** Only HTTP 429 is retried (`maxRetries`, default 2), because the request was rejected before sending. Timeouts and 5xx are never retried: the API has no idempotency key, so a retry could send a duplicate. Record "already sent" in your own database for emails that must not repeat.

## Webhooks

```php
use Inboxili\Webhook;

$ok = Webhook::verify(file_get_contents('php://input'), $_SERVER['HTTP_X_INBOXILI_SIGNATURE'] ?? '', getenv('INBOXILI_WEBHOOK_SECRET'));
```

## Limits of the API

One recipient per request. No attachments, CC, BCC or reply-to fields, and the client rejects unknown fields rather than silently dropping them. No scheduling. PHP's `mail()` and SMTP-only libraries cannot use Inboxili because it has no SMTP endpoint. See the [API reference](https://inboxili.com/transactional-email-api).

## Testing your own code

Inject a fake transport:

```php
$client = new Client('test', transport: $fakeImplementingInboxiliHttpTransport);
```

## Development

```bash
composer install
composer test
```

## Links

[Documentation](https://inboxili.com/docs) · [PHP guide](https://inboxili.com/integrations/php) · [Report a vulnerability](SECURITY.md) · MIT licensed

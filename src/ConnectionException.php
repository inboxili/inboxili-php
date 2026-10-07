<?php

declare(strict_types=1);

namespace Inboxili;

/** Raised when no response was received (network failure or timeout). The email may or may not have been sent. */
final class ConnectionException extends \RuntimeException
{
}

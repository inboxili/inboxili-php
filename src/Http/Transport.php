<?php

declare(strict_types=1);

namespace Inboxili\Http;

interface Transport
{
    /**
     * @param array<string,string> $headers
     * @return array{0:int,1:string} [HTTP status, raw body]
     * @throws \Inboxili\ConnectionException when no response is received
     */
    public function post(string $url, array $headers, string $body, float $timeout): array;
}

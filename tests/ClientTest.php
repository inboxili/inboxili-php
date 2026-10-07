<?php

declare(strict_types=1);

namespace Inboxili\Tests;

use Inboxili\Client;
use Inboxili\ConnectionException;
use Inboxili\Http\Transport;
use Inboxili\InboxiliException;
use Inboxili\Webhook;
use PHPUnit\Framework\TestCase;

final class FakeTransport implements Transport
{
    /** @var list<array{0:int,1:string}|\Throwable> */
    public array $queue = [];
    /** @var list<array{url:string,headers:array<string,string>,body:string}> */
    public array $seen = [];

    public function post(string $url, array $headers, string $body, float $timeout): array
    {
        $this->seen[] = ['url' => $url, 'headers' => $headers, 'body' => $body];
        $next = array_shift($this->queue);
        if ($next instanceof \Throwable) {
            throw $next;
        }
        return $next;
    }
}

final class ClientTest extends TestCase
{
    private function client(FakeTransport $t, int $retries = 2): Client
    {
        return new Client('ik_live_test', transport: $t, maxRetries: $retries, sleep: static function (): void {});
    }

    public function testSendMapsRequestAndResponse(): void
    {
        $t = new FakeTransport();
        $t->queue = [[200, '{"status":"sent","message_id":"m1"}']];
        $out = $this->client($t)->emails->send([
            'to' => 'a@b.co', 'from_email' => 'hi@x.co', 'subject' => 'S',
            'html_body' => '<p>{{n}}</p>', 'template_data' => ['n' => 1], 'from_name' => null,
        ]);

        $this->assertSame('sent', $out->status);
        $this->assertSame('m1', $out->messageId);
        $this->assertSame('https://api.inboxili.com/api/v1/transactional/send', $t->seen[0]['url']);
        $this->assertSame('Bearer ik_live_test', $t->seen[0]['headers']['Authorization']);
        $this->assertSame(
            ['to' => 'a@b.co', 'from_email' => 'hi@x.co', 'subject' => 'S', 'html_body' => '<p>{{n}}</p>', 'template_data' => ['n' => 1]],
            json_decode($t->seen[0]['body'], true)
        );
    }

    public function testValidatesBeforeRequest(): void
    {
        $t = new FakeTransport();
        $c = $this->client($t);
        foreach ([
            ['to' => 'a@b.co', 'from_email' => 'x@y.co', 'subject' => 's'],
            ['to' => 'a@b.co', 'from_email' => 'x@y.co', 'html_body' => 'x'],
            ['to' => 'a@b.co', 'from_email' => 'x@y.co', 'template_id' => 't', 'cc' => 'z@y.co'],
        ] as $bad) {
            try {
                $c->emails->send($bad);
                $this->fail('expected InvalidArgumentException');
            } catch (\InvalidArgumentException) {
            }
        }
        $this->assertSame([], $t->seen);
    }

    public function testApiError(): void
    {
        $t = new FakeTransport();
        $t->queue = [[422, '{"error":{"code":"sender_not_verified","message":"nope"}}']];
        try {
            $this->client($t)->emails->send(['to' => 'a@b.co', 'from_email' => 'x@y.co', 'template_id' => 't']);
            $this->fail('expected exception');
        } catch (InboxiliException $e) {
            $this->assertSame([422, 'sender_not_verified', 'nope'], [$e->status, $e->errorCode, $e->getMessage()]);
        }
    }

    public function testRetries429Only(): void
    {
        $t = new FakeTransport();
        $t->queue = [[429, '{"error":{"code":"rate_limited","message":"slow"}}'], [200, '{"status":"sent","message_id":null}']];
        $out = $this->client($t, 1)->emails->send(['to' => 'a@b.co', 'from_email' => 'x@y.co', 'template_id' => 't']);
        $this->assertNull($out->messageId);
        $this->assertCount(2, $t->seen);

        $t = new FakeTransport();
        $t->queue = [[500, '{}'], [200, '{"status":"sent"}']];
        try {
            $this->client($t)->emails->send(['to' => 'a@b.co', 'from_email' => 'x@y.co', 'template_id' => 't']);
            $this->fail('expected exception');
        } catch (InboxiliException) {
        }
        $this->assertCount(1, $t->seen);
    }

    public function testConnectionErrorPropagates(): void
    {
        $t = new FakeTransport();
        $t->queue = [new ConnectionException('x')];
        $this->expectException(ConnectionException::class);
        $this->client($t)->emails->send(['to' => 'a@b.co', 'from_email' => 'x@y.co', 'template_id' => 't']);
    }

    public function testDebugInfoHidesKey(): void
    {
        ob_start();
        var_dump($this->client(new FakeTransport()));
        $this->assertStringNotContainsString('ik_live_test', (string) ob_get_clean());
    }

    public function testWebhookVerify(): void
    {
        $body = '{"event":"delivered"}';
        $sig = hash_hmac('sha256', $body, 's3cret');
        $this->assertTrue(Webhook::verify($body, $sig, 's3cret'));
        $this->assertFalse(Webhook::verify($body, $sig, 'other'));
        $this->assertFalse(Webhook::verify($body . ' ', $sig, 's3cret'));
    }
}

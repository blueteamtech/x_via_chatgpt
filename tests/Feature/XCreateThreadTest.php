<?php

use App\Mcp\Servers\XConnectorServer;
use App\Mcp\Tools\XCreateThreadTool;
use Illuminate\Support\Facades\Http;

it('chains each post as a reply to the previous one', function () {
    $ids = ['aaa', 'bbb', 'ccc'];
    $seen = [];

    Http::fake([
        '*tweets*' => function ($request) use (&$ids, &$seen) {
            $body = json_decode($request->body(), true);
            $seen[] = [
                'text' => $body['text'] ?? null,
                'reply_to' => $body['reply']['in_reply_to_tweet_id'] ?? null,
            ];

            return Http::response(['data' => ['id' => array_shift($ids)]]);
        },
    ]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XCreateThreadTool::class, ['posts' => ['one', 'two', 'three']])
        ->assertHasNoErrors()
        ->assertSee(['"thread_length":3', '"root_post_id":"aaa"']);

    expect($seen)->toHaveCount(3)
        ->and($seen[0]['reply_to'])->toBeNull()
        ->and($seen[1]['reply_to'])->toBe('aaa')
        ->and($seen[2]['reply_to'])->toBe('bbb');
});

it('auto-splits long text at paragraph boundaries', function () {
    $paragraph = str_repeat('word ', 40); // ~200 chars
    $long = trim($paragraph)."\n\n".trim($paragraph)."\n\n".trim($paragraph);

    $captured = [];

    Http::fake([
        '*tweets*' => function ($request) use (&$captured) {
            $body = json_decode($request->body(), true);
            $captured[] = $body['text'];

            return Http::response(['data' => ['id' => 'x'.count($captured)]]);
        },
    ]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XCreateThreadTool::class, ['text' => $long])
        ->assertHasNoErrors();

    expect(count($captured))->toBeGreaterThanOrEqual(2);

    foreach ($captured as $text) {
        expect(mb_strlen($text))->toBeLessThanOrEqual(275);
    }
});

it('stops the thread and reports which post failed on X errors', function () {
    $calls = 0;

    Http::fake([
        '*tweets*' => function () use (&$calls) {
            $calls++;

            if ($calls === 2) {
                return Http::response(['detail' => 'Something broke.'], 400);
            }

            return Http::response(['data' => ['id' => 'ok'.$calls]]);
        },
    ]);

    XConnectorServer::actingAs(connectedUser())
        ->tool(XCreateThreadTool::class, ['posts' => ['one', 'two', 'three']])
        ->assertHasErrors()
        ->assertSee('Thread stopped at post 2/3');

    expect($calls)->toBe(2);
});

<?php

use App\Mcp\Servers\XConnectorServer;
use App\Mcp\Tools\XGetTopPostsTool;
use Illuminate\Support\Facades\Http;

function fakeTweet(string $id, string $createdAt, array $publicMetrics = [], array $nonPublicMetrics = []): array
{
    return [
        'id' => $id,
        'text' => "tweet {$id}",
        'created_at' => $createdAt,
        'public_metrics' => array_merge([
            'like_count' => 0,
            'retweet_count' => 0,
            'reply_count' => 0,
            'quote_count' => 0,
            'bookmark_count' => 0,
            'impression_count' => 0,
        ], $publicMetrics),
        'non_public_metrics' => $nonPublicMetrics,
    ];
}

it('ranks tweets by the chosen metric and returns only the top N', function () {
    Http::fake([
        '*users/*/tweets*' => Http::response([
            'data' => [
                fakeTweet('1', now()->subDays(2)->toIso8601String(), ['like_count' => 5]),
                fakeTweet('2', now()->subDays(3)->toIso8601String(), ['like_count' => 42]),
                fakeTweet('3', now()->subDays(4)->toIso8601String(), ['like_count' => 17]),
            ],
            'meta' => ['result_count' => 3],
        ]),
    ]);

    XConnectorServer::actingAs(connectedUser(['x_id' => '999']))
        ->tool(XGetTopPostsTool::class, ['sort_by' => 'likes', 'limit' => 2])
        ->assertHasNoErrors()
        ->assertSee(['"sorted_by":"likes"', '"id":"2"', '"id":"3"'])
        ->assertDontSee('"id":"1"');
});

it('stops paginating once tweets fall outside the days_back window', function () {
    $recent = fakeTweet('1', now()->subDays(2)->toIso8601String(), ['like_count' => 10]);
    $stale = fakeTweet('2', now()->subDays(100)->toIso8601String(), ['like_count' => 999]);

    $pageCount = 0;

    Http::fake([
        '*users/*/tweets*' => function () use (&$pageCount, $recent, $stale) {
            $pageCount++;

            return Http::response([
                'data' => [$recent, $stale],
                'meta' => ['result_count' => 2, 'next_token' => 'more'],
            ]);
        },
    ]);

    XConnectorServer::actingAs(connectedUser(['x_id' => '999']))
        ->tool(XGetTopPostsTool::class, ['days_back' => 30])
        ->assertHasNoErrors();

    expect($pageCount)->toBe(1);
});

it('analyzes another user by @username after resolving to a user id', function () {
    Http::fake([
        '*users/by/username/naval*' => Http::response(['data' => ['id' => '745273', 'username' => 'naval']]),
        '*users/745273/tweets*' => Http::response([
            'data' => [
                fakeTweet('a', now()->subDays(1)->toIso8601String(), ['like_count' => 10]),
                fakeTweet('b', now()->subDays(2)->toIso8601String(), ['like_count' => 99]),
            ],
            'meta' => ['result_count' => 2],
        ]),
    ]);

    XConnectorServer::actingAs(connectedUser(['x_id' => '999']))
        ->tool(XGetTopPostsTool::class, ['username' => '@naval', 'sort_by' => 'likes', 'limit' => 1])
        ->assertHasNoErrors()
        ->assertSee(['"account":"@naval"', '"id":"b"']);

    // Confirm we did NOT ask X for non_public_metrics on another user's tweets.
    Http::assertSent(fn ($request) => str_contains($request->url(), '/users/745273/tweets')
        && ! str_contains($request->url(), 'non_public_metrics'));
});

it('rejects sorting a foreign user by impressions', function () {
    Http::fake();

    XConnectorServer::actingAs(connectedUser(['x_id' => '999']))
        ->tool(XGetTopPostsTool::class, ['username' => 'someone', 'sort_by' => 'impressions'])
        ->assertHasErrors()
        ->assertSee('Impressions are only available');

    Http::assertNothingSent();
});

it('excludes replies and retweets by default', function () {
    Http::fake(['*users/*/tweets*' => Http::response(['data' => [], 'meta' => ['result_count' => 0]])]);

    XConnectorServer::actingAs(connectedUser(['x_id' => '999']))
        ->tool(XGetTopPostsTool::class, [])
        ->assertHasNoErrors();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'exclude=replies%2Cretweets'));
});
